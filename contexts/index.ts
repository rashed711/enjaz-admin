import { serve } from 'https://deno.land/std@0.177.0/http/server.ts';
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'
declare const Deno: any;
import { corsHeaders } from '../../_shared/cors.ts'

// Define a simple error structure for consistent error handling
interface HttpError {
  status: number;
  message: string;
}

serve(async (req) => {
  // Handle CORS preflight requests
  if (req.method === 'OPTIONS') {
    return new Response('ok', { headers: corsHeaders })
  }

  try {
    // --- Step 1: Authenticate the user making the request ---
    // This is a critical security check. It ensures only users with the 'admin' role can update other users.
    // This part of the code is correct and necessary for security.
    const authHeader = req.headers.get('Authorization');
    if (!authHeader) {
      throw { status: 401, message: 'Missing Authorization header' } as HttpError;
    }

    const userSupabaseClient = createClient(
      Deno.env.get('SUPABASE_URL') ?? '',
      Deno.env.get('SUPABASE_ANON_KEY') ?? '',
      { global: { headers: { Authorization: authHeader } } }
    );

    const { data: { user: adminUser }, error: authError } = await userSupabaseClient.auth.getUser();
    if (authError || !adminUser || adminUser.app_metadata?.role !== 'admin') {
      throw { status: 401, message: authError?.message || 'Unauthorized: User is not an admin.' } as HttpError;
    }

    // --- Step 2: Get the user ID and the new data from the request body ---
    const { userId, ...payload } = await req.json();
    if (!userId) {
      throw { status: 400, message: 'userId is required' } as HttpError;
    }

    // --- Step 3: Create a Supabase client with admin privileges ---
    const supabaseAdmin = createClient(
      Deno.env.get('SUPABASE_URL') ?? '',
      Deno.env.get('SUPABASE_SERVICE_ROLE_KEY') ?? ''
    );

    // --- Step 4: Fetch the user's current data BEFORE any updates ---
    // This is a defensive step to ensure we have all existing metadata.
    const { data: { user: existingUser }, error: getUserError } = await supabaseAdmin.auth.admin.getUserById(userId);
    if (getUserError || !existingUser) {
      throw { status: 404, message: `User with ID ${userId} not found.` };
    }

    // --- Step 5: Build the update payload carefully (THE DEFINITIVE FIX) ---
    // We start with the existing metadata and only add new values if they are valid (not empty).
    // This prevents accidental overwrites with empty strings.
    const authUpdatePayload: {
      email?: string;
      password?: string;
      user_metadata: Record<string, any>;
      app_metadata: Record<string, any>;
    } = {
      // THE FIX: Start with existing metadata, but use `|| {}` to prevent errors if it's null.
      user_metadata: { ...(existingUser.user_metadata || {}) },
      app_metadata: { ...(existingUser.app_metadata || {}) },
    };

    // Only update email if it's a non-empty string and has changed.
    if (payload.email && typeof payload.email === 'string' && payload.email !== existingUser.email) {
      authUpdatePayload.email = payload.email;
    }
    // Only update password if it's a non-empty string.
    if (payload.password && typeof payload.password === 'string') {
      authUpdatePayload.password = payload.password;
    }
    // THE FIX: Only update name if it's a non-empty string. This is the most critical part.
    if (typeof payload.name === 'string' && payload.name.trim()) {
      authUpdatePayload.user_metadata.name = payload.name.trim();
    }
    // Only update role if it's a non-empty string.
    if (typeof payload.role === 'string' && payload.role) {
      authUpdatePayload.app_metadata.role = payload.role;
    }

    // --- Step 6: Perform the update on `auth.users` ---
    // The database trigger (from Step 1) will handle syncing to the `profiles` table automatically.
    // We do NOT sync manually from the function anymore.
    const { data: authUpdateData, error: authUpdateError } = await supabaseAdmin.auth.admin.updateUserById(
      userId,
      authUpdatePayload
    );

    if (authUpdateError) {
      // Re-throw the original Supabase error
      throw authUpdateError;
    }

    if (!authUpdateData?.user) {
      throw new Error('Failed to update user or retrieve updated user data.');
    }

    // --- Step 7: Return the successfully updated user object ---
    return new Response(JSON.stringify(authUpdateData.user), {
      headers: { ...corsHeaders, 'Content-Type': 'application/json' },
      status: 200,
    });

  } catch (error) {
    // Centralized error handling
    const status = error.status || 500;
    const message = error.message || 'Internal Server Error';
    console.error('[update-user-function] Error:', { status, message, originalError: error });
    return new Response(JSON.stringify({ error: message }), {
      headers: { ...corsHeaders, 'Content-Type': 'application/json' },
      status,
    });
  }
});
