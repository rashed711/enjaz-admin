
import { Product, ProductType, Unit } from '../types';

export const mockProducts: Product[] = [
  // FIX: Added missing properties 'productType' and 'unit' to align with the Product interface.
  { id: 1, name: 'تصميم هوية بصرية', description: 'تصميم هوية بصرية كاملة تشمل الشعار والألوان والخطوط.', unitPrice: 5000, productType: ProductType.SIMPLE, unit: Unit.COUNT },
  // FIX: Added missing properties 'productType' and 'unit' to align with the Product interface.
  { id: 2, name: 'تطوير موقع إلكتروني', description: 'موقع تعريفي للشركة مكون من 5 صفحات.', unitPrice: 12000, productType: ProductType.SIMPLE, unit: Unit.COUNT },
  // FIX: Added missing properties 'productType' and 'unit' to align with the Product interface.
  { id: 3, name: 'حملة تسويق رقمي', description: 'حملة تسويق رقمي لمدة 3 أشهر على منصات التواصل الاجتماعي.', unitPrice: 9000, productType: ProductType.SIMPLE, unit: Unit.COUNT },
  // FIX: Added missing properties 'productType' and 'unit' to align with the Product interface.
  { id: 4, name: 'تصوير منتجات', description: 'تصوير منتج واحد احترافي بخلفية بيضاء.', unitPrice: 100, productType: ProductType.SIMPLE, unit: Unit.COUNT },
  // FIX: Added missing properties 'productType' and 'unit' to align with the Product interface.
  { id: 5, name: 'إدارة حسابات تواصل', description: 'إدارة شهرية لحسابات التواصل الاجتماعي.', unitPrice: 3000, productType: ProductType.SIMPLE, unit: Unit.COUNT },
];
