
export enum Role {
  CLIENT = 'Client',
  SALES_EMPLOYEE = 'Sales Employee',
  SALES_MANAGER = 'Sales Manager',
  ACCOUNTING_MANAGER = 'Accounting Manager',
  ACCOUNTING_EMPOYEE = 'Accounting Employee',
  CEO = 'CEO',
}

export interface User {
  id: string; // Stays string for Supabase UUID
  name: string;
  email: string;
  role: Role;
}

export enum Currency {
  EGP = 'EGP',
  SAR = 'SAR',
  USD = 'USD',
}

// NEW: Enums for product classification
export enum ProductType {
  SIMPLE = 'Accessories', // منتج بسيط
  DIFFUSER = 'Air Outlets', // دفيوزر (أبعاد ثنائية)
  CABLE_TRAY = 'Cable Tray' // كيبل تراي (أبعاد ثلاثية)
}

export enum Unit {
  COUNT = 'No',
  METER = 'MT',
  TON = 'Ton',
  Kilogram = 'Kg'
}

export interface Product {
  id: number;
  name: string;
  description: string;
  unitPrice: number;
  productType: ProductType; // ADDED
  unit: Unit; // ADDED
}

export interface QuotationItem {
  id?: number;
  productId?: number;
  description: string;
  quantity: number;
  unitPrice: number;
  total: number;
  unit?: Unit; // ADDED
  length?: number; // ADDED
  width?: number; // ADDED
  height?: number; // ADDED
}

export interface Quotation {
  id?: number;
  quotationNumber: string;
  clientName: string;
  company: string;
  project: string;
  quotationType: string;
  date: string;
  currency: Currency;
  items: QuotationItem[];
  totalAmount: number;
  createdBy: string; // Stays string for Supabase UUID
}