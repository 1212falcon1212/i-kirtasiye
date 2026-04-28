export interface WishlistItem {
  id: number;
  product_id: number;
  user_id: number;
  created_at: string;
  product?: {
    id: number;
    name: string;
    barcode: string;
    brand?: string;
    image?: string;
    lowest_price?: number;
  };
}
