const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api';

interface ApiResponse<T> {
  data?: T;
  error?: string;
  errors?: Record<string, string[]>; // Laravel validation errors
  status: number;
}

// In-memory GET cache with TTL
interface CacheEntry<T> {
  data: ApiResponse<T>;
  expiresAt: number;
}

const apiCache = new Map<string, CacheEntry<unknown>>();
const inflight = new Map<string, Promise<ApiResponse<unknown>>>();

const DEFAULT_CACHE_TTL = 60_000; // 1 minute
const CACHE_TTL_MAP: Record<string, number> = {
  '/categories': 300_000,            // 5 min - rarely changes
  '/cms/layout': 300_000,            // 5 min
  '/cms/homepage': 120_000,          // 2 min
  '/cms/featured-sections': 120_000, // 2 min - market home recently sold + featured
  '/cms/random-products': 60_000,    // 1 min - market home all products grid
  '/cms/pages': 0,                   // CMS pages: no client cache; admin edits reflect on refresh
  '/cms/pages-by-group': 0,          // Group sidebar lists also fetch fresh
  '/brands': 300_000,                // 5 min
  '/brands/featured': 300_000,       // 5 min
  '/landing-content': 300_000,       // 5 min - landing page content
  '/blog': 0,                        // no cache - admin changes reflect instantly
  '/locations': 86_400_000,          // 24h - city/district list rarely changes
};

function getCacheTTL(endpoint: string): number {
  for (const [prefix, ttl] of Object.entries(CACHE_TTL_MAP)) {
    if (endpoint.startsWith(prefix)) return ttl;
  }
  // Products and search get default TTL
  if (endpoint.startsWith('/products')) return DEFAULT_CACHE_TTL;
  return 0; // No cache for other endpoints (auth, cart, orders, etc.)
}

class ApiClient {
  private token: string | null = null;

  setToken(token: string | null) {
    this.token = token;
    if (token) {
      localStorage.setItem('token', token);
    } else {
      localStorage.removeItem('token');
    }
  }

  getToken(): string | null {
    if (this.token) return this.token;
    if (typeof window !== 'undefined') {
      this.token = localStorage.getItem('token');
    }
    return this.token;
  }

  private handleUnauthorized(endpoint?: string) {
    console.error('[API] 401 Unauthorized on endpoint:', endpoint);
    // Clear token
    this.token = null;
    if (typeof window !== 'undefined') {
      localStorage.removeItem('token');
      // Redirect to login page (Note: (auth) is a route group, URL is /login not /auth/login)
      window.location.href = '/login';
    }
  }

  private async request<T>(
    endpoint: string,
    options: RequestInit = {}
  ): Promise<ApiResponse<T>> {
    const token = this.getToken();

    const headers: Record<string, string> = {
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };

    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    try {
      const response = await fetch(`${API_URL}${endpoint}`, {
        ...options,
        headers,
      });

      const data = await response.json();

      if (!response.ok) {
        // 401 Unauthorized - Token expired or invalid
        // But NOT for login/register endpoints (they naturally return 401 for wrong credentials)
        // 401 redirect only for endpoints that truly require auth
        // Skip redirect for cart, legal, public pages, and auth endpoints
        const skipRedirectPrefixes = ['/auth/', '/cart', '/legal/', '/cms/', '/categories', '/products', '/brands'];
        const shouldRedirect = response.status === 401 && !skipRedirectPrefixes.some(p => endpoint.startsWith(p));
        if (shouldRedirect) {
          this.handleUnauthorized(endpoint);
        }
        return {
          error: data.message || 'Bir hata oluştu',
          errors: data.errors, // Include Laravel validation errors
          status: response.status,
        };
      }

      return {
        data,
        status: response.status,
      };
    } catch (error) {
      return {
        error: 'Sunucuya bağlanılamadı',
        status: 500,
      };
    }
  }

  async get<T>(endpoint: string): Promise<ApiResponse<T>> {
    const ttl = getCacheTTL(endpoint);

    if (ttl > 0) {
      // Check cache
      const cached = apiCache.get(endpoint) as CacheEntry<T> | undefined;
      if (cached && cached.expiresAt > Date.now()) {
        return cached.data as ApiResponse<T>;
      }

      // Deduplicate inflight requests to the same endpoint
      const existing = inflight.get(endpoint);
      if (existing) {
        return existing as Promise<ApiResponse<T>>;
      }

      const promise = this.request<T>(endpoint, { method: 'GET' }).then((result) => {
        if (result.data) {
          apiCache.set(endpoint, { data: result, expiresAt: Date.now() + ttl });
        }
        inflight.delete(endpoint);
        return result;
      });

      inflight.set(endpoint, promise as Promise<ApiResponse<unknown>>);
      return promise;
    }

    return this.request<T>(endpoint, { method: 'GET' });
  }

  /** Invalidate cached GET responses matching a prefix */
  invalidateCache(prefix?: string): void {
    if (!prefix) {
      apiCache.clear();
      return;
    }
    for (const key of apiCache.keys()) {
      if (key.startsWith(prefix)) {
        apiCache.delete(key);
      }
    }
  }

  async getBlob(endpoint: string): Promise<{ blob?: Blob; error?: string; contentType?: string }> {
    const token = this.getToken();

    const headers: Record<string, string> = {
      'Accept': '*/*',
    };

    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    try {
      const response = await fetch(`${API_URL}${endpoint}`, {
        method: 'GET',
        headers,
      });

      if (!response.ok) {
        if (response.status === 401) {
          this.handleUnauthorized(endpoint);
        }
        return { error: 'Dosya indirilemedi' };
      }

      const blob = await response.blob();
      const contentType = response.headers.get('content-type') || 'application/octet-stream';
      return { blob, contentType };
    } catch (error) {
      return { error: 'Sunucuya bağlanılamadı' };
    }
  }

  async post<T>(endpoint: string, body?: object): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, {
      method: 'POST',
      body: body ? JSON.stringify(body) : undefined,
    });
  }

  async put<T>(endpoint: string, body?: object): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, {
      method: 'PUT',
      body: body ? JSON.stringify(body) : undefined,
    });
  }

  async patch<T>(endpoint: string, body?: object): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, {
      method: 'PATCH',
      body: body ? JSON.stringify(body) : undefined,
    });
  }

  async delete<T>(endpoint: string): Promise<ApiResponse<T>> {
    return this.request<T>(endpoint, { method: 'DELETE' });
  }

  async postFormData<T>(endpoint: string, formData: FormData): Promise<ApiResponse<T>> {
    const token = this.getToken();
    const headers: Record<string, string> = {};

    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    try {
      const response = await fetch(`${API_URL}${endpoint}`, {
        method: 'POST',
        headers,
        body: formData,
      });

      const data = await response.json();

      if (!response.ok) {
        // 401 Unauthorized - Token expired or invalid (but not for auth endpoints)
        if (response.status === 401) {
          this.handleUnauthorized(endpoint);
        }
        return {
          error: data.message || 'Bir hata oluştu',
          status: response.status,
        };
      }

      return {
        data,
        status: response.status,
      };
    } catch (error) {
      return {
        error: 'Sunucuya bağlanılamadı',
        status: 500,
      };
    }
  }
}

export const api = new ApiClient();

// Auth API
export const authApi = {
  login: (email: string, password: string) =>
    api.post<{ user: User; token: string; message: string }>('/auth/login', { email, password }),

  register: (data: RegisterData) =>
    api.post<{ user: User; token: string; message: string }>('/auth/register', data),

  logout: () => api.post('/auth/logout'),

  getUser: () => api.get<{ user: User }>('/auth/user'),

  verifyVergiNo: (vergi_no: string) =>
    api.post<VergiNoVerificationResult>('/auth/verify-vergi-no', { vergi_no }),

  changePassword: (data: { current_password: string; new_password: string; new_password_confirmation: string }) =>
    api.post<{ success: boolean; message: string }>('/auth/change-password', data),

  deactivateAccount: (data: { password: string; reason?: string }) =>
    api.post<{ success: boolean; message: string }>('/auth/deactivate-account', data),

  updateProfile: (data: { nickname?: string; phone?: string; address?: string; city?: string; district?: string; trade_name?: string; kep_address?: string; mersis_no?: string; tax_number?: string; tax_office?: string }) =>
    api.put<{ success: boolean; message: string; user: User }>('/auth/update-profile', data),

  forgotPassword: (email: string) =>
    api.post<{ message: string }>('/auth/forgot-password', { email }),

  resetPassword: (data: { email: string; token: string; password: string; password_confirmation: string }) =>
    api.post<{ token: string; message: string }>('/auth/reset-password', data),

  verifyEmail: (data: { id: string; hash: string; expires: string; signature: string }) =>
    api.post<{ message: string }>('/auth/email/verify', data),

  resendVerification: () =>
    api.post<{ message: string }>('/auth/email/resend'),
};

// Documents API
export const documentsApi = {
  getAll: () => api.get<DocumentsResponse>('/documents'),

  upload: (type: string, file: File) => {
    const formData = new FormData();
    formData.append('type', type);
    formData.append('file', file);
    return api.postFormData<DocumentUploadResponse>('/documents/upload', formData);
  },

  delete: (id: number) => api.delete<{ message: string }>(`/documents/${id}`),

  getStatus: () => api.get<DocumentStatusResponse>('/documents/status'),
};

// Contracts API
export const contractsApi = {
  downloadRegistration: () =>
    api.getBlob('/contracts/registration/download'),

  uploadSigned: (file: File) => {
    const formData = new FormData();
    formData.append('file', file);
    return api.postFormData<{ success: boolean; message: string; document: SellerDocument }>(
      '/contracts/registration/upload',
      formData
    );
  },

  downloadSalesContract: (orderId: number, sellerId?: number) =>
    api.getBlob(`/orders/${orderId}/sales-contract${sellerId ? `?seller_id=${sellerId}` : ''}`),
};

// Products API
export const productsApi = {
  getAll: (params?: {
    page?: number;
    per_page?: number;
    category?: string;
    brand?: string;
    min_price?: string;
    max_price?: string;
    sort_by?: string;
    search?: string;
  }) => {
    const query = new URLSearchParams();
    if (params?.page) query.set('page', String(params.page));
    if (params?.per_page) query.set('per_page', String(params.per_page));
    if (params?.category) query.set('category', params.category);
    if (params?.brand) query.set('brand', params.brand);
    if (params?.min_price) query.set('min_price', params.min_price);
    if (params?.max_price) query.set('max_price', params.max_price);
    if (params?.sort_by) query.set('sort_by', params.sort_by);
    if (params?.search) query.set('search', params.search);
    return api.get<ProductsResponse>(`/products?${query}`);
  },

  search: (q: string, page = 1) =>
    api.get<ProductsResponse>(`/products/search?q=${encodeURIComponent(q)}&page=${page}`),

  get: (id: number) => api.get<{ product: Product }>(`/products/${id}`),

  getOffers: (productId: number) =>
    api.get<ProductOffersResponse>(`/products/${productId}/offers`),
};

// Seller Offers Response (for seller profile page)
export interface SellerOffersResponse {
  seller: {
    id: number;
    business_name: string;
    nickname?: string;
    city?: string;
    seller_score?: number | null;
    seller_review_count?: number;
  };
  offers: Offer[];
  pagination: Pagination;
}

// Offers API
export const offersApi = {
  getMyOffers: (params?: { page?: number; per_page?: number; status?: string }) => {
    const query = new URLSearchParams();
    if (params?.page) query.set('page', String(params.page));
    if (params?.per_page) query.set('per_page', String(params.per_page));
    if (params?.status) query.set('status', params.status);
    return api.get<OffersResponse>(`/my-offers?${query}`);
  },

  create: (data: CreateOfferData) => api.post<{ offer: Offer }>('/offers', data),

  update: (id: number, data: UpdateOfferData) =>
    api.put<{ offer: Offer }>(`/offers/${id}`, data),

  delete: (id: number) => api.delete(`/offers/${id}`),

  toggleStatus: (id: number) => api.post<{ offer: Offer; message: string }>(`/offers/${id}/toggle-status`),

  getSellerOffers: (sellerId: number, page?: number) =>
    api.get<SellerOffersResponse>(`/sellers/${sellerId}/offers?page=${page || 1}`),
};

// Categories API
export const categoriesApi = {
  getAll: () => api.get<CategoriesResponse>('/categories'),
  get: (id: number) => api.get<CategoryResponse>(`/categories/${id}`),
};

// Cart API
export const cartApi = {
  get: () => api.get<CartResponse>('/cart'),

  addItem: (offerId: number, quantity: number = 1) =>
    api.post<CartActionResponse>('/cart/items', { offer_id: offerId, quantity }),

  updateQuantity: (itemId: number, quantity: number) =>
    api.put<CartActionResponse>(`/cart/items/${itemId}`, { quantity }),

  removeItem: (itemId: number) =>
    api.delete<CartActionResponse>(`/cart/items/${itemId}`),

  validate: () => api.post<CartValidationResponse>('/cart/validate'),

  clear: () => api.delete<CartActionResponse>('/cart'),
};

// Orders API
export const ordersApi = {
  getAll: (params?: { page?: number; per_page?: number }) => {
    const query = new URLSearchParams();
    if (params?.page) query.set('page', String(params.page));
    if (params?.per_page) query.set('per_page', String(params.per_page));
    return api.get<OrdersResponse>(`/orders?${query}`);
  },

  getSellerOrders: (params?: { page?: number; per_page?: number; status?: string }) => {
    const query = new URLSearchParams();
    if (params?.page) query.set('page', String(params.page));
    if (params?.per_page) query.set('per_page', String(params.per_page));
    if (params?.status) query.set('status', params.status);
    return api.get<SellerOrdersResponse>(`/orders/seller?${query}`);
  },

  get: (id: number) => api.get<OrderResponse>(`/orders/${id}`),

  create: (data: CreateOrderData) =>
    api.post<{ message: string; order: Order; order_number: string }>('/orders', data),

  updateStatus: (id: number, status: string) =>
    api.put<{ message: string; sub_order?: SubOrder; order?: Order; order_status?: string }>(`/orders/${id}/status`, { status }),

  cancel: (id: number, subOrderId?: number) =>
    api.put<{ message: string; order: Order; sub_order?: SubOrder }>(`/orders/${id}/cancel`, subOrderId ? { sub_order_id: subOrderId } : {}),

  confirmDelivery: (id: number, subOrderId?: number) =>
    api.put<{ message: string; order: Order }>(`/orders/${id}/confirm-delivery`, subOrderId ? { sub_order_id: subOrderId } : {}),
};

// Address API
export interface Address {
  id: number;
  title: string;
  name: string;
  phone: string;
  address: string;
  city: string;
  district: string;
  city_id?: number;
  district_id?: number;
  postal_code?: string;
  is_default: boolean;
}

export interface AddressResponse {
  data: Address;
  message?: string;
}

export interface AddressListResponse {
  data: Address[];
}

export const addressApi = {
  getAll: () => api.get<AddressListResponse>('/user/addresses'),
  create: (data: Partial<Address>) => api.post<AddressResponse>('/user/addresses', data),
  update: (id: number, data: Partial<Address>) => api.put<AddressResponse>(`/user/addresses/${id}`, data),
  delete: (id: number) => api.delete(`/user/addresses/${id}`),
};

export interface CityOption {
  id: number;
  code: number;
  name: string;
  slug: string;
}

export interface DistrictOption {
  id: number;
  city_id: number;
  name: string;
  slug: string;
}

export const locationsApi = {
  getCities: () =>
    api.get<{ success: boolean; data: CityOption[] }>('/locations/cities'),
  getDistricts: (cityId: number) =>
    api.get<{ success: boolean; data: DistrictOption[] }>(
      `/locations/cities/${cityId}/districts`,
    ),
};

// Types
export interface User {
  id: number;
  email: string;
  vergi_no: string;
  business_name: string;
  nickname?: string; // Rumuz
  display_name?: string; // nickname veya business_name
  phone?: string;
  address?: string;
  city?: string;
  district?: string;
  city_id?: number;
  district_id?: number;
  trade_name?: string;
  kep_address?: string;
  mersis_no?: string;
  tax_number?: string;
  tax_office?: string;
  is_verified: boolean;
  role: 'super-admin' | 'retailer' | 'seller';
  created_at?: string;
}

export interface RegisterData {
  email: string;
  password: string;
  password_confirmation: string;
  role: 'retailer' | 'seller';
  vergi_no?: string;
  business_name?: string;
  nickname: string;
  phone?: string;
  address?: string;
  city?: string;
  district?: string;
}

export interface VergiNoVerificationResult {
  whitelisted: boolean;
  already_registered?: boolean;
  company_name?: string;
  business_name?: string;
  city?: string;
  district?: string;
  address?: string;
  message?: string;
}

export interface Category {
  id: number;
  name: string;
  slug: string;
  full_slug?: string;
  description?: string;
  image_url?: string | null;
  commission_rate: number;
  is_active: boolean;
  products_count?: number;
  children?: Category[];
}

export interface Product {
  id: number;
  slug?: string;
  barcode: string;
  name: string;
  brand?: string;
  manufacturer?: string;
  description?: string;
  image?: string;
  image_url?: string;
  category_id?: number;
  category?: Category;
  is_active: boolean;
  psf?: number | string | null;
  offers_count?: number;
  lowest_price?: number;
  highest_price?: number;
}

export interface Offer {
  id: number;
  product_id: number;
  seller_id: number;
  price: number;
  stock: number;
  expiry_date: string;
  batch_number?: string;
  status: 'pending' | 'active' | 'inactive' | 'sold_out' | 'rejected';
  notes?: string;
  rejection_reason?: string;
  reviewed_at?: string;
  product?: Product;
  seller?: {
    id: number;
    business_name: string;
    nickname?: string;
    city?: string;
    role?: 'retailer' | 'seller';
    seller_score?: number | null;
    seller_review_count?: number;
  };
  campaigns?: {
    id: number;
    name: string;
    type: string;
    discount_rate?: number;
    min_purchase_amount?: number;
    min_quantity?: number;
  }[];
}

export interface CreateOfferData {
  product_id: number;
  price: number;
  stock: number;
  expiry_date?: string;
  batch_number?: string;
  notes?: string;
}

export interface UpdateOfferData {
  price?: number;
  stock?: number;
  expiry_date?: string;
  batch_number?: string;
  status?: 'pending' | 'active' | 'inactive' | 'sold_out' | 'rejected';
  notes?: string;
}

export interface CartItem {
  id: number;
  cart_id: number;
  product_id: number;
  offer_id: number;
  seller_id: number;
  quantity: number;
  price_at_addition: number;
  product: Product;
  offer: Offer;
  seller: {
    id: number;
    business_name: string;
    city?: string;
    role?: 'retailer' | 'seller';
  };
}

export interface CartBySeller {
  seller: {
    id: number;
    business_name: string;
    city?: string;
    role?: 'retailer' | 'seller';
  };
  items: CartItem[];
  subtotal: number;
}

export interface ShippingAddress {
  name: string;
  phone: string;
  address: string;
  city: string;
  district?: string;
  city_id?: number;
  district_id?: number;
  postal_code?: string;
}

export interface SubOrderInvoice {
  id: number;
  invoice_number: string;
  status: string;
  status_label: string;
  total_amount: number;
  formatted_total: string;
  pdf_path?: string;
  created_at: string;
}

export interface SubOrder {
  id: number;
  seller_id: number;
  seller_name: string;
  status: 'pending' | 'confirmed' | 'processing' | 'shipped' | 'delivered' | 'cancelled' | 'returned';
  status_label: string;
  shipped_at?: string;
  delivered_at?: string;
  buyer_confirmed_at?: string;
  tracking_number?: string;
  shipping_provider?: string;
  subtotal: number;
  item_count: number;
  invoice?: SubOrderInvoice | null;
}

export interface Order {
  id: number;
  order_number: string;
  user_id: number;
  subtotal: number;
  total_commission: number;
  total_amount: number;
  shipping_cost?: number;
  status: 'pending' | 'confirmed' | 'processing' | 'shipped' | 'delivered' | 'cancelled' | 'returned';
  payment_status: 'pending' | 'paid' | 'failed' | 'refunded' | 'expired';
  payment_method?: string;
  shipping_address: ShippingAddress;
  shipping_provider?: string;
  tracking_number?: string;
  shipping_label_url?: string;
  shipped_at?: string;
  delivered_at?: string;
  buyer_confirmed_at?: string;
  notes?: string;
  created_at: string;
  items?: OrderItem[];
  sub_orders?: SubOrder[];
  buyer?: {
    id: number;
    business_name: string;
    email: string;
    phone?: string;
  };
  seller?: {
    id: number;
    business_name: string;
    name?: string;
    city?: string;
  };
}

export interface OrderItem {
  id: number;
  order_id: number;
  sub_order_id?: number;
  product_id: number;
  offer_id: number;
  seller_id: number;
  quantity: number;
  unit_price: number;
  total_price: number;
  commission_rate: number;
  commission_amount: number;
  seller_payout_amount: number;
  product?: Product;
  seller?: {
    id: number;
    business_name: string;
    nickname?: string;
    city?: string;
    role?: string;
  };
}

export interface CreateOrderData {
  shipping_address: ShippingAddress;
  notes?: string;
  shipping_provider?: string;
  shipping_cost?: number;
  payment_method?: string;
}

export interface Pagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface ProductsResponse {
  products: Product[];
  pagination: Pagination;
  filters?: {
    brands: string[];
    subcategories: { id: number; name: string; slug: string }[];
    category: Category | null;
  };
}

export interface OffersResponse {
  offers: Offer[];
  pagination: Pagination;
}

export interface ProductOffersResponse {
  product: Product;
  offers: Offer[];
  offers_count: number;
  lowest_price: number;
  highest_price: number;
}

export interface CategoriesResponse {
  categories: Category[];
}

export interface CategoryResponse {
  category: Category;
  products: Product[];
}

export interface CartResponse {
  cart: object | null;
  items: CartItem[];
  items_by_seller: CartBySeller[];
  item_count: number;
  total: number;
}

export interface CartActionResponse {
  message: string;
  item?: CartItem;
  item_count: number;
  total: number;
}

export interface CartValidationResponse {
  valid: boolean;
  issues: {
    item_id: number;
    product_name: string;
    type: 'unavailable' | 'stock' | 'price_changed';
    message: string;
    available_stock?: number;
    old_price?: number;
    new_price?: number;
  }[];
}

export interface OrdersResponse {
  orders: Order[];
  pagination: Pagination;
}

export interface OrderResponse {
  order: Order;
  items: OrderItem[];
  items_by_seller: {
    seller: { id: number; business_name: string; city?: string };
    items: OrderItem[];
    subtotal: number;
    commission: number;
    payout: number;
  }[];
}

export interface SellerOrder {
  id: number;
  sub_order_id?: number;
  order_number: string;
  status: string;
  status_label: string;
  shipping_status?: string;
  tracking_number?: string;
  buyer: {
    id: number;
    business_name: string;
    email: string;
    phone?: string;
  } | null;
  shipping_address: ShippingAddress | null;
  items: OrderItem[];
  seller_total: number;
  seller_commission: number;
  seller_payout: number;
  created_at: string;
}

export interface SellerOrdersResponse {
  orders: SellerOrder[];
  pagination: Pagination;
}

// Document types
export interface SellerDocument {
  id: number;
  type: string;
  type_label: string;
  original_name: string;
  file_url: string;
  status: 'pending' | 'approved' | 'rejected';
  status_label: string;
  rejection_reason?: string;
  created_at: string;
  reviewed_at?: string;
}

export interface DocumentsResponse {
  documents: SellerDocument[];
  required_types: { type: string; label: string }[];
  missing_types: string[];
  rejected_types: string[];
  all_approved: boolean;
  type_labels: Record<string, string>;
  status_labels: Record<string, string>;
}

export interface DocumentUploadResponse {
  message: string;
  document: SellerDocument;
}

export interface DocumentStatusResponse {
  documents_approved: boolean;
  has_pending: boolean;
  has_rejected: boolean;
  required_count: number;
  approved_count: number;
}

// Payment types
export interface PaymentConfig {
  enabled: boolean;
  gateway: 'none' | 'iyzico' | 'paytr';
  test_mode: boolean;
}

export interface PaymentInitResponse {
  success: boolean;
  payment_url?: string;
  checkout_html?: string;
  transaction_id?: string;
  gateway?: string;
  error?: string;
  order?: {
    id: number;
    order_number: string;
    total_amount: number;
  };
}

export interface PaymentCheckoutResponse {
  success: boolean;
  checkout_html?: string;
  gateway?: string;
  error?: string;
}

export interface PaymentStatusResponse {
  success: boolean;
  payment_amount?: string;
  payment_total?: string;
  payment_date?: string;
  currency?: string;
  taksit?: string;
  kart_marka?: string;
  masked_pan?: string;
  net_tutar?: string;
  returns?: Array<{ amount: string; date: string }>;
  error?: string;
}

// PayTR Direct API Types
export interface BinQueryResponse {
  status: string;
  cardType: string;
  bank: string;
  brand: string;
  schema: string;
  businessCard: boolean;
  allow_non3d: boolean;
}

export interface InstallmentRate {
  installment_count: number;
  rate: number;
}

export interface InstallmentRates {
  [cardBrand: string]: InstallmentRate[];
}

export interface SavedCard {
  ctoken: string;
  last_4: string;
  require_cvv: boolean;
  month: string;
  year: string;
  c_bank: string;
  c_name: string;
  c_brand: string;
  c_type: string;
  schema: string;
  businessCard: boolean;
}

export interface PaymentProcessRequest {
  order_id: number;
  card_number?: string;
  expiry_month?: string;
  expiry_year?: string;
  cvv: string;
  cc_owner?: string;
  installment_count?: number;
  store_card?: boolean;
  ctoken?: string;
}

export interface PaymentProcessResponse {
  status: 'success' | 'failed' | '3d_redirect' | 'redirect';
  html?: string;
  error?: string;
}

// Payment API
export const paymentsApi = {
  getConfig: () =>
    api.get<PaymentConfig>('/payments/config'),

  initialize: (orderId: number) =>
    api.post<PaymentInitResponse>('/payments/initialize', { order_id: orderId }),

  getCheckout: (orderId: number) =>
    api.get<PaymentCheckoutResponse>(`/payments/${orderId}/checkout`),

  refund: (orderId: number, amount?: number) =>
    api.post<{ success: boolean; refund_id?: string; refunded_amount?: number; error?: string }>(
      `/payments/${orderId}/refund`,
      amount ? { amount } : undefined
    ),

  queryStatus: (orderId: number) =>
    api.post<PaymentStatusResponse>('/payments/status-query', { order_id: orderId }),

  process: (data: PaymentProcessRequest) =>
    api.post<PaymentProcessResponse>('/payments/process', data),

  binQuery: (binNumber: string) =>
    api.post<BinQueryResponse>('/payments/bin-query', { bin_number: binNumber }),

  getInstallments: () =>
    api.get<InstallmentRates>('/payments/installments'),

  getSavedCards: () =>
    api.get<{ cards: SavedCard[] }>('/payments/saved-cards'),

  deleteSavedCard: (ctoken: string) =>
    api.delete<{ success: boolean }>(`/payments/saved-cards/${ctoken}`),
};

// Wallet types
export interface WalletSummary {
  balance: number;
  pending_balance: number;
  total_balance: number;
  withdrawn_balance: number;
  total_earned: number;
  total_commission: number;
}

export interface PayoutEstimate {
  next_payout_date: string;
  next_payout_formatted: string;
  available_balance: number;
  withholding_rate: number;
  withholding_amount: number;
  net_payout_amount: number;
  min_amount: number;
  is_eligible: boolean;
}

export interface WalletTransaction {
  id: number;
  type: string;
  type_label: string;
  amount: number;
  direction: 'credit' | 'debit';
  signed_amount: number;
  description: string | null;
  order_id: number | null;
  created_at: string;
}

export interface BankAccount {
  id: number;
  bank_name: string;
  iban: string;
  masked_iban: string;
  formatted_iban: string;
  account_holder: string;
  tax_id?: string;
  tax_office?: string;
  kep_address?: string;
  mersis_number?: string;
  phone?: string;
  is_default: boolean;
  is_verified: boolean;
}

export interface PayoutRequest {
  id: number;
  amount: number;
  status: string;
  status_label: string;
  notes: string | null;
  admin_notes: string | null;
  created_at: string;
  processed_at: string | null;
  bank_account?: BankAccount;
}

// Settlement types
export interface SettlementGroup {
  date: string;
  date_formatted: string;
  days_remaining: number;
  total_sales: number;
  total_service_fee: number;
  total_withholding_tax: number;
  total_shipping_share: number;
  total_refunds?: number;
  net_amount: number;
  order_count: number;
  item_count: number;
}

export interface SettlementSummary {
  total_sales: number;
  total_service_fee: number;
  total_withholding_tax: number;
  total_shipping_share: number;
  net_estimated_total: number;
}

export interface SettlementSummaryRow {
  label: string;
  description: string;
  amount: number;
  type: 'credit' | 'debit' | 'total';
}

export interface SettlementOrderProduct {
  product_name: string;
  quantity: number;
  unit_price: number;
  total_price: number;
}

export interface SettlementDetailItem {
  order_number: string;
  order_date: string;
  item_count: number;
  total_price: number;
  service_fee: number;
  withholding_tax: number;
  shipping_share: number;
  net_amount: number;
  items: SettlementOrderProduct[];
}

export interface SettlementsResponse {
  upcoming_summary: SettlementSummary;
  upcoming: SettlementGroup[];
  past: SettlementGroup[];
  wallet: { balance: number; pending_balance: number };
  total_gross_sales: number;
  confirmed_order_count: number;
}

export interface SettlementDetailsResponse {
  summary: SettlementSummaryRow[];
  details: SettlementDetailItem[];
}

// Wallet API
export const walletApi = {
  getSummary: () =>
    api.get<{ wallet: WalletSummary; payout_estimate?: PayoutEstimate }>('/wallet'),

  getTransactions: (limit = 20) =>
    api.get<{ transactions: WalletTransaction[] }>(`/wallet/transactions?limit=${limit}`),

  getBankAccounts: () =>
    api.get<{ bank_accounts: BankAccount[] }>('/wallet/bank-accounts'),

  addBankAccount: (data: { bank_name: string; iban: string; account_holder: string; swift_code?: string; tax_id?: string; tax_office?: string; kep_address?: string; mersis_number?: string; phone?: string }) =>
    api.post<{ success: boolean; message: string; bank_account?: BankAccount; error?: string }>('/wallet/bank-accounts', data),

  updateBankAccount: (id: number, data: { bank_name?: string; iban?: string; account_holder?: string; swift_code?: string; tax_id?: string; tax_office?: string; kep_address?: string; mersis_number?: string; phone?: string }) =>
    api.put<{ success: boolean; message: string; bank_account?: BankAccount; error?: string }>(`/wallet/bank-accounts/${id}`, data),

  deleteBankAccount: (id: number) =>
    api.delete<{ success: boolean; message: string; error?: string }>(`/wallet/bank-accounts/${id}`),

  setDefaultBankAccount: (id: number) =>
    api.post<{ success: boolean; message: string }>(`/wallet/bank-accounts/${id}/default`),

  getPayoutRequests: () =>
    api.get<{ payout_requests: PayoutRequest[] }>('/wallet/payout-requests'),

  createPayoutRequest: (data: { amount: number; bank_account_id: number; notes?: string }) =>
    api.post<{ success: boolean; message: string; payout_request?: PayoutRequest; error?: string }>('/wallet/payout-requests', data),

  getSettlements: () =>
    api.get<SettlementsResponse>('/wallet/settlements'),

  getSettlementDetails: (date: string, type: 'upcoming' | 'past') =>
    api.get<SettlementDetailsResponse>(`/wallet/settlements/${date}/details?type=${type}`),
};

// Shipping types
export interface ShippingConfig {
  flat_rate: number;
  free_threshold: number;
  provider: string;
  enabled: boolean;
}

export interface ShippingCalculation {
  shipping_cost: number;
  is_free: boolean;
  remaining_for_free: number;
  free_threshold: number;
}

export interface ShippingTrack {
  success: boolean;
  status: string;
  status_label: string;
  tracking_number?: string;
  tracking_url?: string;
  current_location?: string;
  history?: Array<{ date: string; status: string; location?: string }>;
  error?: string;
}

export interface ShippingOption {
  provider: string;
  name: string;
  price: number;
  original_price: number;
  formatted_price: string;
  is_free: boolean;
  remaining_for_free: number | null;
  remaining_for_free_formatted: string | null;
}

export interface ShippingOptionsResponse {
  success: boolean;
  options: ShippingOption[];
  total_desi: number;
  order_amount: number;
}

// Shipping API
export const shippingApi = {
  getConfig: () =>
    api.get<ShippingConfig>('/shipping/config'),

  calculate: (subtotal: number) =>
    api.post<ShippingCalculation>('/shipping/calculate', { subtotal }),

  getOptions: (totalDesi: number, orderAmount: number) =>
    api.post<ShippingOptionsResponse>('/shipping/options', { total_desi: totalDesi, order_amount: orderAmount }),

  createShipment: (
    orderId: number,
    options?: {
      piece_count?: number;
      is_cod?: boolean;
      cod_amount?: number;
      cod_collection_type?: '0' | '1';
      total_desi?: number;
      total_weight?: number;
    }
  ) =>
    api.post<{ success: boolean; message: string; tracking_number?: string; label_url?: string; error?: string }>(
      `/shipping/orders/${orderId}/shipment`,
      options ?? {}
    ),

  track: (orderId: number) =>
    api.get<ShippingTrack>(`/shipping/orders/${orderId}/track`),

  // Kargo etiketini HTML olarak çek, yeni sekmede aç
  openLabel: async (orderId: number): Promise<{ success: boolean; error?: string }> => {
    try {
      const response = await fetch(`${API_URL}/shipping/orders/${orderId}/label?format=html`, {
        headers: {
          Accept: 'text/html',
          Authorization: `Bearer ${api.getToken() ?? ''}`,
        },
      });
      if (!response.ok) {
        return { success: false, error: `Etiket alınamadı (HTTP ${response.status})` };
      }
      const html = await response.text();
      const blob = new Blob([html], { type: 'text/html' });
      const url = URL.createObjectURL(blob);
      window.open(url, '_blank');
      setTimeout(() => URL.revokeObjectURL(url), 60_000);
      return { success: true };
    } catch (error) {
      return { success: false, error: error instanceof Error ? error.message : 'Bilinmeyen hata' };
    }
  },

  shippingDetail: (orderId: number) =>
    api.get<{ success: boolean; detail: Record<string, unknown> | null; tracking_number?: string; provider?: string }>(
      `/shipping/orders/${orderId}/shipping-detail`
    ),
};

// Seller Shipping Settings (satıcı bazlı kargo override)
export interface SellerShippingSettings {
  default_shipping_fee: number | null;
  free_shipping_threshold: number | null;
  effective_shipping_fee: number;
  effective_threshold: number;
  global: {
    shipping_fee: number;
    free_threshold: number;
  };
  uses_override: boolean;
}

export const sellerShippingApi = {
  get: () => api.get<SellerShippingSettings>('/seller/shipping-settings'),

  update: (payload: { default_shipping_fee: number | null; free_shipping_threshold: number | null }) =>
    api.patch<SellerShippingSettings & { message: string }>(
      '/seller/shipping-settings',
      payload
    ),
};

// Integrations Types
export interface IntegrationCredentials {
  api_key: string | null;
  api_secret: string | null;
  app_id: string | null;
  username: string | null;
  password: string | null;
  test_mode: boolean;
  wsdl_url: string | null;
}

export interface UserIntegration {
  id: number;
  erp_type: string;
  status: 'active' | 'inactive' | 'error' | 'pending';
  last_sync_at: string | null;
  error_message: string | null;
  is_configured: boolean;
  credentials?: IntegrationCredentials;
}

export const integrationsApi = {
  getAll: () => api.get<{ data: UserIntegration[] }>('/settings/integrations'),

  save: (data: { erp_type: string; api_key: string; api_secret: string; app_id?: string; extra_params?: Record<string, any> }) =>
    api.post<{ message: string; data: UserIntegration }>('/settings/integrations', data),

  sync: (erpType: string) => api.post<{ message: string }>('/settings/integrations/' + erpType + '/sync'),

  delete: (erpType: string) => api.delete<{ message: string }>('/settings/integrations/' + erpType),
};

export interface NotificationSetting {
  id: number;
  channel: 'sms' | 'email' | 'push';
  type: string;
  is_enabled: boolean;
}

export const notificationsApi = {
  getAll: () => api.get<{ settings: NotificationSetting[] }>('/settings/notifications'),
  update: (data: { channel: string; type: string; is_enabled: boolean }) =>
    api.post<{ message: string; setting: NotificationSetting }>('/settings/notifications', data),
};


// User Notification types
export interface UserNotification {
  id: number;
  type: 'order_created' | 'new_order' | 'order_confirmed' | 'order_shipped' | 'order_delivered' | 'buyer_confirmed' | 'wallet_released' | 'price_drop' | 'wishlist_added' | 'welcome' | 'order_cancelled';
  title: string;
  body: string;
  data: Record<string, unknown> | null;
  is_read: boolean;
  read_at: string | null;
  created_at: string;
}

// User Notifications API (in-app notifications)
export const userNotificationsApi = {
  getAll: () =>
    api.get<{ notifications: UserNotification[]; unread_count: number; pending_orders_count: number }>('/notifications'),
  getUnreadCount: () =>
    api.get<{ unread_count: number; pending_orders_count: number }>('/notifications/unread-count'),
  markAsRead: (id: number) =>
    api.post<{ message: string }>(`/notifications/${id}/read`),
  markAllAsRead: () =>
    api.post<{ message: string }>('/notifications/read-all'),
};

export const wishlistApi = {
  getAll: () => api.get<any>('/wishlist'), // TODO: Define type
  toggle: (productId: number, targetPrice?: number) =>
    api.post<{ message: string; in_wishlist: boolean }>('/wishlist/toggle', { product_id: productId, target_price: targetPrice }),
};

export const legalApi = {
  getDocument: (slug: string) => api.get<{ content: string; version: string; title?: string; meta_title?: string; meta_description?: string }>(`/legal/items/${slug}`),
  approveContract: (type: string, version: string) =>
    api.post<{ message: string }>('/legal/approve', { type, version }),
};

// CMS Types
export interface Banner {
  id: number;
  title: string;
  subtitle?: string;
  badge_text?: string;
  image_url: string;
  link_url?: string;
  button_text?: string;
  tab_name?: string;
  bg_color?: string;
}

export interface NavigationMenuItem {
  id: number;
  title: string;
  url?: string;
  icon?: string;
  open_in_new_tab: boolean;
  children?: NavigationMenuItem[];
}

export interface HomepageSectionProduct {
  id: number;
  name: string;
  barcode: string;
  brand?: string;
  image?: string;
  category?: string;
  lowest_price?: number;
  offers_count: number;
}

export interface HomepageSection {
  id: number;
  title: string;
  subtitle?: string;
  type: string;
  settings?: Record<string, any>;
  products: HomepageSectionProduct[];
}

export interface CategoryBrand {
  name: string;
  slug: string;
  logo: string | null;
}

export interface CategoryItem {
  id: number;
  name: string;
  slug: string;
  full_slug?: string;
  icon: string;
  products_count: number;
  children?: CategoryItem[];
  top_brands?: CategoryBrand[];
}

export interface FooterSettings {
  description: string;
  phone: string;
  phone_raw: string;
  email: string;
  address?: string;
  hours_weekday?: string;
  hours_saturday?: string;
  hours_sunday?: string;
  copyright: string;
  retailer_note: string;
  facebook_url: string;
  twitter_url: string;
  instagram_url: string;
  linkedin_url: string;
}

export interface CmsLayoutResponse {
  menus: {
    header: NavigationMenuItem[];
    footer: NavigationMenuItem[];
    categories: NavigationMenuItem[];
    mobile: NavigationMenuItem[];
  };
  settings: {
    site_name: string;
    logo_url: string;
    show_top_bar?: boolean;
    top_bar_phone?: string;
    top_bar_hours?: string;
    top_bar_shipping?: string;
    navbar_color?: string;
    whatsapp_phone?: string;
    whatsapp_message?: string;
  };
  footer_settings?: FooterSettings;
}

export interface CmsHomepageResponse {
  banners: {
    hero: Banner[];
    promo?: Banner[];
    middle: Banner[];
    brand?: Banner[];
    grid?: Banner[];
    bottom?: Banner[];
    showcase?: Banner[];
  };
  sections: HomepageSection[];
  categories: CategoryItem[];
  brands?: { id: number; name: string; slug: string; logo?: string | null }[];
  best_sellers?: Product[];
  recommended?: Product[];
  seo_text?: { title: string; content: string };
  category_sections?: CategorySection[];
}

export interface CategorySection {
  category_id: number;
  category_name: string;
  category_slug: string;
  products: Product[];
}

// Featured Section Types
export interface FeaturedOffer {
  id: number;
  product_id: number;
  name: string;
  price: number;
  seller: string;
  seller_id: number;
  stock: number;
  image?: string;
  image_url?: string;
}

export interface RecentlySoldItem {
  id: number;
  product_id: number;
  name: string;
  price: number;
  stock: number;
  offers_count?: number;
  image?: string;
  image_url?: string;
}

export interface FeaturedSectionsResponse {
  season_highlights: FeaturedOffer[];
  week_products: FeaturedOffer[];
  recently_sold: RecentlySoldItem[];
  deal_of_day: FeaturedOffer | null;
}

// CMS Page Type
export interface CmsPage {
  id: number;
  title: string;
  slug: string;
  content: string;
  excerpt: string | null;
  meta_title: string | null;
  meta_description: string | null;
  template: string;
}

export interface CmsPageSummary {
  id: number;
  slug: string;
  title: string;
  excerpt: string | null;
  sort_order: number;
}

// CMS API
export const cmsApi = {
  getLayout: () => api.get<CmsLayoutResponse>('/cms/layout'),
  getHomepage: () => api.get<CmsHomepageResponse>('/cms/homepage'),
  getBanners: (location: string) => api.get<Banner[]>(`/cms/banners/${location}`),
  getFeaturedSections: () => api.get<FeaturedSectionsResponse>('/cms/featured-sections'),
  getRandomProducts: () => api.get<{ products: Product[] }>('/cms/random-products'),
  getPage: (slug: string) => api.get<CmsPage>(`/cms/pages/${slug}`),
  getPagesByGroup: (group: string) =>
    api.get<CmsPageSummary[]>(`/cms/pages-by-group/${group}`),
};

// Seller Dashboard Types
export interface SellerStat {
  value: number;
  formatted: string;
  change?: string;
  trend?: 'up' | 'down';
  pending?: string;
}

export interface SellerStatsResponse {
  success: boolean;
  data: {
    total_sales: SellerStat;
    active_offers: SellerStat;
    pending_orders: SellerStat;
    wallet_balance: SellerStat;
  };
}

export interface SellerRecentOrder {
  id: number;
  order_number: string;
  product: string;
  buyer: string;
  amount: string;
  status: string;
  status_label: string;
  created_at: string;
}

export interface SellerRecentOrdersResponse {
  success: boolean;
  data: SellerRecentOrder[];
}

export interface SellerProduct {
  id: number;
  offer_id: number;
  name: string;
  barcode: string;
  brand: string;
  image?: string;
  category?: string;
  price: number;
  stock: number;
  status: string;
  expiry_date?: string;
}

export interface SellerProductsResponse {
  success: boolean;
  data: SellerProduct[];
  pagination: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface SellerOrderItem {
  id: number;
  product_name: string;
  brand: string;
  quantity: number;
  unit_price: number;
  total: number;
}

export interface SellerDashboardOrder {
  id: number;
  sub_order_id?: number;
  order_number: string;
  seller_buyer: {
    name: string;
    city: string;
  };
  seller_items: SellerOrderItem[];
  total: number;
  formatted_total: string;
  status: string;
  status_label: string;
  payment_status: string;
  created_at: string;
}

export interface SellerOrdersResponse {
  success: boolean;
  data: SellerDashboardOrder[];
  pagination: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

// Seller Order Detail with Fee Breakdown
export interface SellerOrderFinancials {
  subtotal: { label: string; value: number; formatted: string };
  deductions: Array<{
    label: string;
    rate: number | null;
    value: number;
    formatted: string;
    visible?: boolean;
  }>;
  total_deductions: { label: string; value: number; formatted: string };
  total_refunded?: { label: string; value: number; formatted: string };
  net_amount: { label: string; value: number; formatted: string };
}

export interface SellerOrderDetail {
  id: number;
  sub_order_id?: number;
  order_number: string;
  status: string;
  status_label: string;
  payment_status: string;
  shipping_status?: string;
  tracking_number?: string;
  shipping_provider?: string;
  shipping_label_url?: string;
  created_at: string;
  shipped_at?: string;
  delivered_at?: string;
  buyer_confirmed_at?: string;
  buyer: {
    name: string;
    invoice_name?: string;
    email?: string;
    phone?: string;
    city?: string;
    district?: string;
    address?: string;
  } | null;
  items: Array<{
    id: number;
    product_id: number;
    product_name: string;
    brand?: string;
    barcode?: string;
    image?: string;
    desi?: number;
    quantity: number;
    unit_price: number;
    total_price: number;
  }>;
  financials: SellerOrderFinancials;
  can_create_invoice: boolean;
  can_create_label: boolean;
  invoice?: {
    id: number;
    invoice_number: string;
    status: string;
    total_amount: number;
    formatted_total: string;
    created_at: string;
    pdf_path?: string;
  };
}

export interface SellerOrderDetailResponse {
  success: boolean;
  data: SellerOrderDetail;
}

// Seller API
export const sellerApi = {
  getStats: () => api.get<SellerStatsResponse>('/seller/stats'),
  getRecentOrders: (limit?: number) => api.get<SellerRecentOrdersResponse>(`/seller/recent-orders${limit ? `?limit=${limit}` : ''}`),
  getProducts: (page?: number, perPage?: number) => api.get<SellerProductsResponse>(`/seller/products?page=${page || 1}&per_page=${perPage || 15}`),
  getOrders: (status?: string, page?: number, perPage?: number) => api.get<SellerOrdersResponse>(`/seller/orders?page=${page || 1}&per_page=${perPage || 15}${status ? `&status=${status}` : ''}`),
  getOrderDetail: (orderId: number) => api.get<SellerOrderDetailResponse>(`/seller/orders/${orderId}`),
};

// Invoice Types
export interface Invoice {
  id: number;
  invoice_number: string;
  type: 'seller' | 'commission' | 'tax' | 'shipping';
  type_label: string;
  status: 'draft' | 'pending' | 'sent' | 'paid' | 'cancelled';
  status_label: string;
  subtotal: number;
  tax_rate?: number;
  tax_amount: number;
  total_amount: number;
  formatted_total: string;
  commission_rate?: number;
  commission_amount?: number;
  order_number?: string;
  order_id?: number;
  buyer_name?: string;
  seller_info?: Record<string, string>;
  buyer_info?: Record<string, string>;
  items?: Array<Record<string, any>>;
  erp_status: 'pending' | 'synced' | 'failed';
  erp_provider?: string;
  erp_invoice_id?: string;
  pdf_path?: string;
  created_at: string;
}

export interface InvoiceListResponse {
  success: boolean;
  data: Invoice[];
  pagination: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface CommissionSummaryResponse {
  success: boolean;
  data: {
    total_sales: { value: number; formatted: string };
    total_commission: { value: number; formatted: string };
    total_payout: { value: number; formatted: string };
    order_count: number;
    item_count: number;
    average_commission_rate: string;
  };
}

export interface CreateInvoiceResponse {
  success: boolean;
  message?: string;
  invoice?: {
    id: number;
    invoice_number: string;
    formatted_total: string;
  };
  error?: string;
}

// Invoice API
export const invoiceApi = {
  list: (type?: string, page?: number, perPage?: number) =>
    api.get<InvoiceListResponse>(`/invoices?page=${page || 1}&per_page=${perPage || 15}${type ? `&type=${type}` : ''}`),
  get: (id: number) => api.get<{ success: boolean; data: Invoice }>(`/invoices/${id}`),
  createForOrder: (orderId: number) => api.post<CreateInvoiceResponse>(`/invoices/orders/${orderId}`),
  getCommissionSummary: (startDate?: string, endDate?: string) =>
    api.get<CommissionSummaryResponse>(`/invoices/commission-summary${startDate ? `?start_date=${startDate}` : ''}${endDate ? `${startDate ? '&' : '?'}end_date=${endDate}` : ''}`),
  syncToErp: (invoiceId: number) => api.post<{ success: boolean; message?: string; erp_invoice_id?: string }>(`/invoices/${invoiceId}/sync-erp`),
  createViaErp: (orderId: number) => api.post<CreateInvoiceResponse>(`/invoices/orders/${orderId}/erp`),
};

// Distributor-Retailer Link Types
export interface DistributorRetailerLink {
  id: number;
  distributor_id: number;
  retailer_id: number;
  status: 'pending' | 'approved' | 'rejected';
  message?: string;
  rejection_reason?: string;
  approved_at?: string;
  rejected_at?: string;
  created_at: string;
  updated_at: string;
  distributor?: {
    id: number;
    business_name: string;
    nickname?: string;
    email: string;
    phone?: string;
  };
  retailer?: {
    id: number;
    business_name: string;
    nickname?: string;
    city?: string;
    vergi_no?: string;
  };
}

export interface RetailerListItem {
  id: number;
  business_name: string;
  nickname?: string;
  city?: string;
  vergi_no?: string;
  link_status?: 'pending' | 'approved' | 'rejected' | null;
  link_id?: number | null;
}

export interface RetailerListResponse {
  data: RetailerListItem[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface LinkListResponse {
  data: DistributorRetailerLink[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

// Distributor-Retailer Link API
// Maps to backend `DistributorRetailerLinkController` (/distributor-retailer-links/*)
export const distributorRetailerLinkApi = {
  // For distributors (sellers)
  listRetailers: (search?: string, page?: number) =>
    api.get<RetailerListResponse>(`/distributor-retailer-links/retailers?page=${page || 1}${search ? `&search=${encodeURIComponent(search)}` : ''}`),
  sendRequest: (retailerId: number, message?: string) =>
    api.post<{ message: string; link: DistributorRetailerLink }>('/distributor-retailer-links/request', { retailer_id: retailerId, message }),
  mySentRequests: (page?: number) =>
    api.get<LinkListResponse>(`/distributor-retailer-links/my-requests?page=${page || 1}`),
  cancelRequest: (linkId: number) =>
    api.delete<{ message: string }>(`/distributor-retailer-links/request/${linkId}`),
  approvedRetailerIds: () =>
    api.get<{ retailer_ids: number[] }>('/distributor-retailer-links/approved-retailer-ids'),

  // For retailers
  myReceivedRequests: (status?: string, page?: number) =>
    api.get<LinkListResponse>(`/distributor-retailer-links/incoming?page=${page || 1}${status ? `&status=${status}` : ''}`),
  pendingCount: () =>
    api.get<{ count: number }>('/distributor-retailer-links/pending-count'),
  approveRequest: (linkId: number) =>
    api.post<{ message: string; link: DistributorRetailerLink }>(`/distributor-retailer-links/${linkId}/approve`),
  rejectRequest: (linkId: number, reason?: string) =>
    api.post<{ message: string; link: DistributorRetailerLink }>(`/distributor-retailer-links/${linkId}/reject`, { reason }),
  revokeLink: (linkId: number) =>
    api.delete<{ message: string }>(`/distributor-retailer-links/${linkId}/revoke`),
};

// Campaign Types
export interface Campaign {
  id: number;
  seller_id: number;
  name: string;
  description?: string;
  type: 'product_discount' | 'store_discount' | 'brand_discount' | 'gift_product';
  type_label?: string;
  discount_rate?: number;
  min_purchase_amount?: number;
  min_quantity?: number;
  product_id?: number;
  brand?: string;
  gift_product_id?: number;
  gift_quantity?: number;
  starts_at: string;
  ends_at: string;
  status: 'pending' | 'active' | 'inactive' | 'rejected' | 'expired';
  status_label?: string;
  rejection_reason?: string;
  reviewed_at?: string;
  created_at: string;
  product?: Product;
  gift_product?: Product;
  seller?: {
    id: number;
    business_name: string;
    nickname?: string;
    city?: string;
  };
}

export interface CreateCampaignData {
  name: string;
  description?: string;
  type: 'product_discount' | 'store_discount' | 'brand_discount' | 'gift_product';
  discount_rate?: number;
  min_purchase_amount?: number;
  min_quantity?: number;
  product_id?: number;
  brand?: string;
  gift_product_id?: number;
  gift_quantity?: number;
  starts_at: string;
  ends_at: string;
}

export interface CampaignsResponse {
  campaigns: Campaign[];
  pagination: Pagination;
}

// Campaigns API
export const campaignsApi = {
  getAll: (params?: { status?: string; page?: number; per_page?: number }) => {
    const query = new URLSearchParams();
    if (params?.status) query.set('status', params.status);
    if (params?.page) query.set('page', String(params.page));
    if (params?.per_page) query.set('per_page', String(params.per_page));
    return api.get<CampaignsResponse>(`/campaigns?${query}`);
  },
  create: (data: CreateCampaignData) =>
    api.post<{ message: string; campaign: Campaign }>('/campaigns', data),
  get: (id: number) =>
    api.get<{ campaign: Campaign }>(`/campaigns/${id}`),
  update: (id: number, data: Partial<CreateCampaignData>) =>
    api.put<{ message: string; campaign: Campaign }>(`/campaigns/${id}`, data),
  delete: (id: number) =>
    api.delete<{ message: string }>(`/campaigns/${id}`),
  toggleStatus: (id: number) =>
    api.post<{ message: string; campaign: Campaign }>(`/campaigns/${id}/toggle-status`),
  getActive: (params?: { type?: string; seller_id?: number; page?: number }) => {
    const query = new URLSearchParams();
    if (params?.type) query.set('type', params.type);
    if (params?.seller_id) query.set('seller_id', String(params.seller_id));
    if (params?.page) query.set('page', String(params.page));
    return api.get<CampaignsResponse>(`/campaigns/active?${query}`);
  },
};

// Coupon Types
export interface Coupon {
  id: number;
  seller_id: number;
  campaign_id?: number;
  code: string;
  name: string;
  description?: string;
  discount_type: 'percentage' | 'fixed';
  discount_type_label?: string;
  discount_value: number;
  formatted_discount?: string;
  min_purchase_amount?: number;
  max_discount_amount?: number;
  usage_limit?: number;
  usage_limit_per_user?: number;
  used_count: number;
  starts_at?: string;
  ends_at?: string;
  status: 'active' | 'inactive' | 'expired';
  status_label?: string;
  created_at: string;
  campaign?: Campaign;
}

export interface CreateCouponData {
  code?: string;
  name: string;
  description?: string;
  campaign_id?: number;
  discount_type: 'percentage' | 'fixed';
  discount_value: number;
  min_purchase_amount?: number;
  max_discount_amount?: number;
  usage_limit?: number;
  usage_limit_per_user?: number;
  starts_at?: string;
  ends_at?: string;
}

export interface CouponsResponse {
  coupons: Coupon[];
  pagination: Pagination;
}

export interface ApplyCouponResponse {
  valid: boolean;
  message: string;
  coupon?: {
    id: number;
    code: string;
    name: string;
    discount_type: string;
    discount_value: number;
    formatted_discount: string;
  };
  discount_amount?: number;
  formatted_discount_amount?: string;
  new_total?: number;
  formatted_new_total?: string;
}

// Coupons API
export const couponsApi = {
  getAll: (params?: { status?: string; page?: number; per_page?: number }) => {
    const query = new URLSearchParams();
    if (params?.status) query.set('status', params.status);
    if (params?.page) query.set('page', String(params.page));
    if (params?.per_page) query.set('per_page', String(params.per_page));
    return api.get<CouponsResponse>(`/coupons?${query}`);
  },
  create: (data: CreateCouponData) =>
    api.post<{ message: string; coupon: Coupon }>('/coupons', data),
  delete: (id: number) =>
    api.delete<{ message: string }>(`/coupons/${id}`),
  toggleStatus: (id: number) =>
    api.post<{ message: string; coupon: Coupon }>(`/coupons/${id}/toggle-status`),
  apply: (code: string, cartTotal: number, sellerId?: number) =>
    api.post<ApplyCouponResponse>('/coupons/apply', {
      code,
      cart_total: cartTotal,
      seller_id: sellerId,
    }),
  remove: () =>
    api.post<{ success: boolean; message: string }>('/coupons/remove'),
};

// Review Types
export interface Review {
  id: number;
  order_item_id: number;
  product_id: number;
  seller_id: number;
  buyer_id: number;
  rating: number;
  delivery_rating?: number;
  quality_rating?: number;
  communication_rating?: number;
  average_rating?: number;
  comment?: string;
  status: 'pending' | 'approved' | 'rejected';
  status_label?: string;
  rejection_reason?: string;
  seller_reply?: string;
  seller_replied_at?: string;
  created_at: string;
  product?: Product;
  seller?: {
    id: number;
    business_name: string;
    nickname?: string;
    city?: string;
  };
  buyer?: {
    id: number;
    business_name: string;
    nickname?: string;
  };
}

export interface SellerRating {
  overall: number;
  delivery: number;
  quality: number;
  communication: number;
  count: number;
}

export interface CreateReviewData {
  order_item_id: number;
  rating: number;
  delivery_rating?: number;
  quality_rating?: number;
  communication_rating?: number;
  comment?: string;
}

export interface ReviewsResponse {
  reviews: Review[];
  ratings?: SellerRating;
  pagination: Pagination;
}

export interface ProductReviewsResponse {
  reviews: Review[];
  summary: {
    average_rating: number;
    total_reviews: number;
    rating_counts: {
      5: number;
      4: number;
      3: number;
      2: number;
      1: number;
    };
  };
  pagination: Pagination;
}

export interface ReviewableItem {
  id: number;
  product_id: number;
  product: Product;
  seller: {
    id: number;
    business_name: string;
    nickname?: string;
    city?: string;
  };
  order: {
    id: number;
    order_number: string;
    delivered_at?: string;
  };
}

// Reviews API
export const reviewsApi = {
  create: (data: CreateReviewData) =>
    api.post<{ message: string; review: Review }>('/reviews', data),
  getSellerReviews: (params?: { status?: string; page?: number; per_page?: number }) => {
    const query = new URLSearchParams();
    if (params?.status) query.set('status', params.status);
    if (params?.page) query.set('page', String(params.page));
    if (params?.per_page) query.set('per_page', String(params.per_page));
    return api.get<ReviewsResponse>(`/reviews/seller?${query}`);
  },
  getMyReviews: (page?: number) =>
    api.get<ReviewsResponse>(`/reviews/my-reviews?page=${page || 1}`),
  getReviewableItems: () =>
    api.get<{ items: ReviewableItem[]; count: number }>('/reviews/reviewable'),
  reply: (reviewId: number, reply: string) =>
    api.post<{ message: string; review: Review }>(`/reviews/${reviewId}/reply`, { reply }),
  getProductReviews: (productId: number, page?: number) =>
    api.get<ProductReviewsResponse>(`/reviews/product/${productId}?page=${page || 1}`),
};

// Return Request Types
export interface ReturnRequest {
  id: number;
  order_id: number;
  order_number?: string;
  order_item_id?: number;
  product?: {
    id: number;
    name: string;
    image?: string;
  };
  type: 'return' | 'cancel';
  type_label: string;
  reason: string;
  reason_label: string;
  reason_detail?: string;
  quantity: number;
  refund_amount?: number;
  formatted_refund?: string;
  status: 'pending' | 'approved' | 'rejected' | 'shipped' | 'received' | 'refunded' | 'cancelled';
  status_label: string;
  seller_note?: string;
  return_tracking_number?: string;
  return_shipping_provider?: string;
  images?: { path: string; url: string }[];
  created_at: string;
  approved_at?: string;
  rejected_at?: string;
  buyer?: {
    id: number;
    business_name: string;
    email: string;
    phone?: string;
  };
}

export interface ReturnReason {
  value: string;
  label: string;
}

export interface CreateReturnRequestData {
  order_id: number;
  sub_order_id?: number;
  order_item_id?: number;
  reason: string;
  reason_detail?: string;
  quantity?: number;
}

// Return Requests API
export const returnsApi = {
  getReasons: () =>
    api.get<{ success: boolean; reasons: ReturnReason[] }>('/returns/reasons'),
  getMyRequests: (page?: number, perPage?: number) =>
    api.get<{ success: boolean; data: ReturnRequest[]; pagination: Pagination }>(`/returns/my-requests?page=${page || 1}&per_page=${perPage || 10}`),
  getSellerRequests: (params?: { status?: string; page?: number; per_page?: number }) => {
    const query = new URLSearchParams();
    if (params?.status) query.set('status', params.status);
    if (params?.page) query.set('page', String(params.page));
    if (params?.per_page) query.set('per_page', String(params.per_page));
    return api.get<{ success: boolean; data: ReturnRequest[]; pagination: Pagination; pending_count: number }>(`/returns/seller-requests?${query}`);
  },
  getOrderRequests: (orderId: number) =>
    api.get<{ success: boolean; data: ReturnRequest[] }>(`/returns/order/${orderId}`),
  create: (data: CreateReturnRequestData, files?: File[]) => {
    const formData = new FormData();
    formData.append('order_id', String(data.order_id));
    if (data.sub_order_id) formData.append('sub_order_id', String(data.sub_order_id));
    if (data.order_item_id) formData.append('order_item_id', String(data.order_item_id));
    formData.append('reason', data.reason);
    if (data.reason_detail) formData.append('reason_detail', data.reason_detail);
    if (data.quantity) formData.append('quantity', String(data.quantity));
    if (files) files.forEach(f => formData.append('images[]', f));
    return api.postFormData<{ success: boolean; message: string; data: ReturnRequest }>('/returns', formData);
  },
  approve: (id: number, note?: string) =>
    api.post<{ success: boolean; message: string; data: ReturnRequest }>(`/returns/${id}/approve`, { note }),
  reject: (id: number, note: string) =>
    api.post<{ success: boolean; message: string; data: ReturnRequest }>(`/returns/${id}/reject`, { note }),
};

// === Support Tickets ===
export interface SupportTicket {
  id: number;
  user_id: number;
  subject: string;
  category: 'order' | 'payment' | 'shipping' | 'product' | 'account' | 'other';
  category_label: string;
  description: string;
  status: 'open' | 'in_progress' | 'waiting' | 'resolved' | 'closed';
  status_label: string;
  order_id?: number | null;
  admin_note?: string | null;
  assigned_to?: number | null;
  resolved_at?: string | null;
  closed_at?: string | null;
  created_at: string;
  updated_at: string;
  messages?: SupportTicketMessage[];
  order?: { id: number; order_number: string; status?: string; total_amount?: number } | null;
  last_message?: SupportTicketMessage | null;
}

export interface SupportTicketAttachment {
  path: string;
  name: string;
  mime: string;
  size: number;
  url?: string;
}

export interface SupportTicketMessage {
  id: number;
  ticket_id: number;
  user_id: number;
  message: string;
  is_staff_reply: boolean;
  attachments?: SupportTicketAttachment[] | null;
  created_at: string;
  user?: { id: number; business_name: string; role: string };
}

export const supportTicketsApi = {
  getAll: (page?: number, perPage?: number) =>
    api.get<{ success: boolean; data: SupportTicket[]; pagination: Pagination }>(`/support-tickets?page=${page || 1}&per_page=${perPage || 15}`),
  get: (id: number) =>
    api.get<{ success: boolean; data: SupportTicket }>(`/support-tickets/${id}`),
  create: (data: { subject: string; category: string; description: string; order_id?: number | null }, files?: File[]) => {
    const formData = new FormData();
    formData.append('subject', data.subject);
    formData.append('category', data.category);
    formData.append('description', data.description);
    if (data.order_id) formData.append('order_id', String(data.order_id));
    if (files) files.forEach(f => formData.append('attachments[]', f));
    return api.postFormData<{ success: boolean; message: string; data: SupportTicket }>('/support-tickets', formData);
  },
  addMessage: (ticketId: number, message: string, files?: File[]) => {
    const formData = new FormData();
    formData.append('message', message);
    if (files) files.forEach(f => formData.append('attachments[]', f));
    return api.postFormData<{ success: boolean; message: string; data: SupportTicketMessage }>(`/support-tickets/${ticketId}/messages`, formData);
  },
  close: (ticketId: number) =>
    api.put<{ success: boolean; message: string }>(`/support-tickets/${ticketId}/close`),
};

// === Blog ===
export interface BlogCategory {
  id: number;
  name: string;
  slug: string;
  description?: string;
  posts_count: number;
}

export interface BlogPost {
  id: number;
  title: string;
  slug: string;
  excerpt?: string;
  content?: string;
  featured_image_url?: string;
  category?: {
    name: string;
    slug: string;
  };
  author?: {
    name: string;
  };
  tags: string[];
  meta_title?: string;
  meta_description?: string;
  published_at: string;
  read_time_minutes?: number;
  view_count?: number;
  is_featured?: boolean;
}

export interface BlogPostsResponse {
  posts: BlogPost[];
  categories: BlogCategory[];
  pagination: Pagination;
}

export interface BlogPostDetailResponse {
  post: BlogPost;
  related_posts: BlogPost[];
}

export const blogApi = {
  getPosts: (params?: { category?: string; page?: number; per_page?: number }) => {
    const query = new URLSearchParams();
    if (params?.category) query.set('category', params.category);
    if (params?.page) query.set('page', String(params.page));
    if (params?.per_page) query.set('per_page', String(params.per_page));
    return api.get<BlogPostsResponse>(`/blog/posts?${query}`);
  },
  getPost: (slug: string) =>
    api.get<BlogPostDetailResponse>(`/blog/posts/${slug}`),
  getCategories: () =>
    api.get<{ status: string; data: BlogCategory[] }>('/blog/categories'),
  getRandom: (limit = 3) =>
    api.get<{ posts: BlogPost[] }>(`/blog/posts/random?limit=${limit}`),
};

// Platform Settings API
export interface FeeInfo {
  fee_mode: 'flat' | 'percentage' | 'category';
  flat_service_fee: number;
  commission_percentage: number;
  withholding_tax_rate: number;
  service_fee_enabled: boolean;
  commission_enabled: boolean;
}

export const platformApi = {
  getFeeInfo: () => api.get<FeeInfo>('/settings/fee-info'),
};
