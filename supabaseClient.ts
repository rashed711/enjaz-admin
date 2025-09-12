import { createClient } from '@supabase/supabase-js';

const supabaseUrl = 'https://syhfndlcapcuelxzbjmi.supabase.co'; // الصق عنوان المشروع هنا
const supabaseAnonKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InN5aGZuZGxjYXBjdWVseHpiam1pIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTc1NzY2MDQsImV4cCI6MjA3MzE1MjYwNH0.P19SO-YcBLgBNdQPIgRmstF3csAnrEjvEJDAmoAmxEo'.trim(); // الصق مفتاح anon public هنا

if (!supabaseUrl || !supabaseAnonKey) {
  throw new Error("Supabase URL and Anon Key are required.");
}

export const supabase = createClient(supabaseUrl, supabaseAnonKey);