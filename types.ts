

export enum Role {
  CLIENT = 'Client',
  SALES_EMPLOYEE = 'Sales Employee',
  SALES_MANAGER = 'Sales Manager',
  ACCOUNTING_MANAGER = 'Accounting Manager',
  ACCOUNTING_EMPLOYEE = 'Accounting Employee',
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
  sellingPrice: number;
  productType: ProductType;
  unit: Unit;
  averagePurchasePrice?: number;
  averageSellingPrice?: number;
}

// Refactored: Generic DocumentItem to replace duplicated item interfaces
export interface DocumentItem {
  id?: number;
  productId?: number;
  description: string;
  quantity: number;
  unitPrice: number;
  total: number;
  unit?: Unit;
  length?: number;
  width?: number;
  height?: number;
}

// Refactored: Generic DocumentItemState for use in editors
export interface DocumentItemState extends DocumentItem {
  productType?: ProductType;
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
  items: DocumentItem[];
  totalAmount: number;
  createdBy: string; // Stays string for Supabase UUID
  taxIncluded: boolean;
  discount: number;
}

// --- New Interfaces for Purchase Invoices ---
export enum PurchaseInvoiceStatus {
  DRAFT = 'Draft',
  PAID = 'Paid',
  CANCELLED = 'Cancelled',
}

export interface PurchaseInvoice {
  id?: number;
  invoiceNumber: string;
  supplierName: string;
  date: string;
  currency: Currency;
  status: PurchaseInvoiceStatus;
  items: DocumentItem[];
  totalAmount: number;
  createdBy: string;
}

// --- New Interfaces for Sales Invoices ---
export enum SalesInvoiceStatus {
  DRAFT = 'Draft',
  SENT = 'Sent',
  PAID = 'Paid',
  OVERDUE = 'Overdue',
  CANCELLED = 'Cancelled',
}

export interface SalesInvoice {
  id?: number;
  invoiceNumber: string;
  clientName: string;
  company: string;
  project: string;
  date: string;
  currency: Currency;
  status: SalesInvoiceStatus;
  items: DocumentItem[];
  totalAmount: number;
  createdBy: string;
  quotationId?: number; // Link to the original quotation
}

// --- New Types for Permissions System ---
export enum PermissionModule {
  QUOTATIONS = 'عروض الأسعار',
  SALES_INVOICES = 'فواتير المبيعات',
  PURCHASE_INVOICES = 'فواتير المشتريات',
  PRODUCTS = 'المنتجات',
  USERS = 'المستخدمين',
  PERMISSIONS = 'الصلاحيات',
}

export enum PermissionAction {
  CREATE = 'create',
  VIEW_OWN = 'view_own',
  VIEW_ALL = 'view_all',
  EDIT_OWN = 'edit_own',
  EDIT_ALL = 'edit_all',
  DELETE_OWN = 'delete_own',
  DELETE_ALL = 'delete_all',
  CHANGE_STATUS = 'change_status',
  MANAGE = 'manage', // A general 'manage' permission
}

export type PermissionsConfig = {
  [key in Role]?: {
    [key in PermissionModule]?: PermissionAction[];
  };
};