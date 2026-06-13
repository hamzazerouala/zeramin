export type UserType = 'buyer' | 'seller' | 'admin';

export interface SellerProfile {
    id: number;
    shop_name: string;
    shop_slug: string;
    logo_url?: string | null;
    banner_url?: string | null;
    bio?: string | null;
    contact_email?: string | null;
    country?: string | null;
    avg_rating?: number;
    is_active?: boolean;
    stripe_connect_id?: string | null;
    aliexpress_account?: string | null;
}

export interface User {
    id: number;
    name: string;
    email: string;
    user_type: UserType;
    phone?: string | null;
    two_factor_enabled?: boolean;
    seller_profile?: SellerProfile | null;
}

export interface ProductImage {
    id: number;
    url: string;
    alt?: string | null;
    sort_order: number;
}

export interface ProductVariant {
    id: number;
    name: string;
    values?: Record<string, string> | null;
    stock: number;
}

export interface Product {
    id: number;
    title: string;
    slug: string;
    description?: string | null;
    price: number;
    cost_price?: number;
    currency: string;
    rating: number;
    rating_count: number;
    in_stock: boolean;
    stock: number;
    featured?: boolean;
    shipping_days?: number | null;
    thumbnail?: string | null;
    category?: { id: number; name: string; slug: string } | null;
    seller?: { id: number; shop_name: string; shop_slug: string; avg_rating?: number } | null;
    images?: ProductImage[];
    variants?: ProductVariant[];
}

export interface CartItem {
    id: number;
    product_id: number;
    variant_id?: number | null;
    title?: string;
    thumbnail?: string | null;
    unit_price: number;
    quantity: number;
    subtotal: number;
}

export interface Cart {
    id: number;
    coupon_code?: string | null;
    currency: string;
    items: CartItem[];
    subtotal: number | null;
    item_count: number | null;
}

export interface OrderItem {
    id: number;
    product_id: number;
    title?: string;
    quantity: number;
    unit_price: number;
    subtotal: number;
}

export interface Order {
    id: number;
    order_number: string;
    status: string;
    payment_status: string;
    subtotal: number;
    shipping_cost: number;
    tax_amount: number;
    total_amount: number;
    currency: string;
    shipping_address?: Record<string, string> | null;
    shipping_method?: string | null;
    tracking?: {
        aliexpress_order_number?: string | null;
        tracking_id?: string | null;
        tracking_url?: string | null;
        estimated_delivery_date?: string | null;
        delivered_at?: string | null;
    };
    items?: OrderItem[];
    created_at?: string;
}

export interface Paginated<T> {
    data: T[];
    meta?: { current_page: number; last_page: number; total: number };
    links?: unknown;
}

export interface CheckoutBreakdown {
    subtotal: number;
    discount: number;
    shipping: number;
    tax: number;
    total: number;
}
