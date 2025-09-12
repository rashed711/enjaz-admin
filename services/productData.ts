
import { Product, ProductType, Unit } from '../types';

export const mockProducts: Product[] = [
  // FIX: Added missing properties 'productType' and 'unit' to align with the Product interface.
  { id: 1, name: 'تصميم هوية بصرية', sellingPrice: 5000, productType: ProductType.SIMPLE, unit: Unit.COUNT },
  // FIX: Added missing properties 'productType' and 'unit' to align with the Product interface.
  { id: 2, name: 'تطوير موقع إلكتروني', sellingPrice: 12000, productType: ProductType.SIMPLE, unit: Unit.COUNT },
  // FIX: Added missing properties 'productType' and 'unit' to align with the Product interface.
  { id: 3, name: 'حملة تسويق رقمي', sellingPrice: 9000, productType: ProductType.SIMPLE, unit: Unit.COUNT },
  // FIX: Added missing properties 'productType' and 'unit' to align with the Product interface.
  { id: 4, name: 'تصوير منتجات', sellingPrice: 100, productType: ProductType.SIMPLE, unit: Unit.COUNT },
  // FIX: Added missing properties 'productType' and 'unit' to align with the Product interface.
  { id: 5, name: 'إدارة حسابات تواصل', sellingPrice: 3000, productType: ProductType.SIMPLE, unit: Unit.COUNT },
];