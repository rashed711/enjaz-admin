

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
  description: string;
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
  productName?: string;
  description: string;
  quantity: number;
  unitPrice: number;
  total: number;
  unit?: Unit;
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
  createdBy: string | null;
  taxIncluded: boolean;
  discount: number;
  creatorName?: string;
  createdAt?: string;
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
  supplierId?: number | null;
  date: string;
  currency: Currency;
  status: PurchaseInvoiceStatus;
  items: DocumentItem[];
  totalAmount: number;
  createdBy: string | null;
  creatorName?: string;
  createdAt?: string;
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
  createdBy: string | null;
  quotationId?: number; // Link to the original quotation
  creatorName?: string;
  createdAt?: string;
}

// --- New Interfaces for Accounting Module ---
export enum PartyType {
  NONE = 'None',
  CUSTOMER = 'Customer',
  SUPPLIER = 'Supplier',
  CUSTOMER_AND_SUPPLIER = 'CustomerAndSupplier',
}

export const partyTypeLabels: Record<PartyType, string> = {
  [PartyType.NONE]: 'لا شيء',
  [PartyType.CUSTOMER]: 'عميل',
  [PartyType.SUPPLIER]: 'مورد',
  [PartyType.CUSTOMER_AND_SUPPLIER]: 'عميل ومورد',
};

export enum AccountType {
  ASSET = 'Asset',
  LIABILITY = 'Liability',
  EQUITY = 'Equity',
  REVENUE = 'Revenue',
  EXPENSE = 'Expense',
}

export interface Account {
  id: number;
  name: string;
  code?: string;
  account_type: AccountType;
  parent_id?: number | null;
  party_type: PartyType;
  children?: Account[]; // For building the tree structure
}

export interface JournalEntry {
  id: number;
  date: string;
  description?: string;
  debit: number;
  credit: number;
  account_id: number;
  account_name?: string; // Joined data
  account_code?: string; // Joined data
  createdBy: string | null;
  creatorName?: string;
  createdAt?: string;
}

export interface Receipt {
    id: number;
    date: string;
    amount: number;
    payment_method: string;
    description?: string;
    account_id: number; // Credit account (e.g., customer)
    cash_account_id: number; // Debit account (e.g., bank/cash)
    createdBy: string | null;
    creatorName?: string;
    account_name?: string;
    cash_account_name?: string;
    createdAt?: string;
}

export interface PaymentVoucher {
    id: number;
    date: string;
    amount: number;
    payment_method: string;
    description?: string;
    account_id: number; // Debit account (e.g., supplier/expense)
    cash_account_id: number; // Credit account (e.g., bank/cash)
    createdBy: string | null;
    creatorName?: string;
    account_name?: string;
    cash_account_name?: string;
    createdAt?: string;
}


// --- New Types for Permissions System ---
export enum PermissionModule {
  QUOTATIONS = 'عروض الأسعار',
  SALES_INVOICES = 'فواتير المبيعات',
  PURCHASE_INVOICES = 'فواتير المشتريات',
  PRODUCTS = 'المنتجات',
  USERS = 'المستخدمين',
  PERMISSIONS = 'الصلاحيات',
  ACCOUNTS = 'الحسابات',
  JOURNAL_ENTRIES = 'القيود اليومية',
  RECEIPTS = 'سندات القبض',
  PAYMENT_VOUCHERS = 'سندات الصرف',
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

// --- Navigation Types ---
// Placing these here to avoid circular dependencies if they were in navigation.ts
// and imported by hooks that navigation.ts also imports.

export interface NavLink {
    path: string;
    label: string;
    Icon: React.FC<React.SVGProps<SVGSVGElement>>;
    roles: Role[];
    inSidebar: boolean;
    inBottomNav: boolean;
    bottomNavLabel?: string;
    title: string;
    children?: NavLinkChild[];
    permission?: [PermissionModule, PermissionAction];
}

export interface NavLinkChild {
    path: string;
    label: string;
    Icon: React.FC<React.SVGProps<SVGSVGElement>>;
    roles: Role[];
    title: string;
    inSubMenu?: boolean;
    permission?: [PermissionModule, PermissionAction];
}