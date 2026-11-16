export interface Category {
    id: number;
    name: string;
    slug?: string;
    description?: string | null;
    created_at: string;
    updated_at: string;
    products_count?: number;
}

export interface ProductPhoto {
    id: number;
    product_id: string;
    url: string;
    is_primary: boolean;
    created_at: string;
    updated_at: string;
}

export interface CustomizationOption {
    id: number;
    product_customization_id: number;
    name: string;
    additional_price: number;
    is_default: boolean;
}

export interface ProductCustomization {
    id: number;
    product_id: string;
    name: string;
    type: string;
    is_required: boolean;
    options?: CustomizationOption[];
}

export interface Product {
    id: string;
    name: string;
    category_id: number;
    price: number;
    base_price?: number;
    description?: string | null;
    slug?: string;
    is_available?: boolean;
    is_active?: boolean;
    is_featured?: boolean;
    image_path?: string | null;
    created_at: string;
    updated_at: string;
    category?: Category;
    photos?: ProductPhoto[];
    customizations?: ProductCustomization[];
}

export interface CartItemOption {
    customization_id: number;
    customization_name: string;
    option_id: number;
    option_name: string;
    additional_price: number;
}

export interface CartItem {
    product: Product;
    quantity: number;
    notes?: string;
    selected_options?: CartItemOption[];
    unit_price?: number;
}

export interface OrderItem {
    id: number;
    order_id: number;
    product_id: string;
    quantity: number;
    notes?: string | null;
    price: number;
    subtotal: number;
    product: Product;
}

export interface Payment {
    id: number;
    order_id: number;
    amount: number;
    status: 'pending' | 'completed' | 'failed';
    payment_method: string | null;
    transaction_id: string | null;
    paid_at: string | null;
    notes?: string | null;
}

export interface Order {
    id: number;
    customer_name: string | null;
    table_number: number;
    status: 'pending' | 'completed' | 'cancelled';
    created_at: string;
    updated_at: string;
    order_items: OrderItem[];
    payment: Payment;
}

export interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    has_more_pages: boolean;
}
