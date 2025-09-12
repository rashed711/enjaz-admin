// This file is now a proxy to ensure a single Supabase client instance is used throughout the app.
// It points to the canonical client configuration in the /services directory.
export { supabase } from './services/supabaseClient';
