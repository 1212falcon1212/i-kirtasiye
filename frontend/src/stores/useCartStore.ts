import { create } from 'zustand';
import { persist } from 'zustand/middleware';
import { cartApi } from '@/lib/api';

export interface CartItem {
    id: number;
    cart_id: number;
    product_id: number;
    offer_id: number;
    seller_id: number;
    quantity: number;
    price_at_addition: number;
    product: {
        id: number;
        name: string;
        barcode: string;
        image?: string;
        image_url?: string;
        brand?: string;
    };
    offer: {
        id: number;
        price: number;
        stock: number;
        expiry_date?: string;
    };
    seller: {
        id: number;
        business_name: string;
        nickname?: string;
        city?: string;
    };
}

export interface CartBySeller {
    seller: {
        id: number;
        business_name: string;
        nickname?: string;
        city?: string;
    };
    items: CartItem[];
    subtotal: number;
}

export interface ValidationIssue {
    item_id: number;
    product_name: string;
    type: 'unavailable' | 'stock' | 'price_changed';
    message: string;
    available_stock?: number;
    old_price?: number;
    new_price?: number;
}

interface CartState {
    items: CartItem[];
    itemsBySeller: CartBySeller[];
    itemCount: number;
    total: number;
    isLoading: boolean;
    isOpen: boolean;
    validationIssues: ValidationIssue[];

    // Seller selection
    selectedSellers: number[];

    // Animation states
    isShaking: boolean;
    lastAddedItemId: number | null;
    removingItemId: number | null;

    // Computed helpers
    selectedTotal: () => number;
    selectedItemsBySeller: () => CartBySeller[];

    // Actions
    setOpen: (open: boolean) => void;
    fetchCart: () => Promise<void>;
    addItem: (offerId: number, quantity?: number) => Promise<void>;
    updateQuantity: (itemId: number, quantity: number) => Promise<void>;
    removeItem: (itemId: number) => Promise<void>;
    clearCart: () => Promise<void>;
    validateCart: () => Promise<boolean>;
    triggerShake: () => void;
    setLastAddedItemId: (id: number | null) => void;
    setRemovingItemId: (id: number | null) => void;
    toggleSeller: (sellerId: number) => void;
    selectAllSellers: () => void;
    deselectAllSellers: () => void;
}

export const useCartStore = create<CartState>()(
    persist(
        (set, get) => ({
            items: [],
            itemsBySeller: [],
            itemCount: 0,
            total: 0,
            isLoading: false,
            isOpen: false,
            validationIssues: [],

            // Seller selection - all selected by default
            selectedSellers: [],

            // Animation states
            isShaking: false,
            lastAddedItemId: null,
            removingItemId: null,

            // Computed helpers
            selectedTotal: () => {
                const state = get();
                if (state.selectedSellers.length === 0) return state.total;
                return state.itemsBySeller
                    .filter(g => g.seller && state.selectedSellers.includes(g.seller.id))
                    .reduce((sum, g) => sum + g.subtotal, 0);
            },
            selectedItemsBySeller: () => {
                const state = get();
                if (state.selectedSellers.length === 0) return state.itemsBySeller;
                return state.itemsBySeller.filter(g => g.seller && state.selectedSellers.includes(g.seller.id));
            },

            setOpen: (open) => set({ isOpen: open }),

            triggerShake: () => {
                set({ isShaking: true });
                setTimeout(() => set({ isShaking: false }), 500);
            },

            setLastAddedItemId: (id) => {
                set({ lastAddedItemId: id });
                if (id) {
                    setTimeout(() => set({ lastAddedItemId: null }), 2000);
                }
            },

            setRemovingItemId: (id) => set({ removingItemId: id }),

            toggleSeller: (sellerId) => {
                const current = get().selectedSellers;
                if (current.includes(sellerId)) {
                    set({ selectedSellers: current.filter(id => id !== sellerId) });
                } else {
                    set({ selectedSellers: [...current, sellerId] });
                }
            },

            selectAllSellers: () => {
                const allIds = get().itemsBySeller.map(g => g.seller?.id).filter((id): id is number => id != null);
                set({ selectedSellers: allIds });
            },

            deselectAllSellers: () => {
                set({ selectedSellers: [] });
            },

            fetchCart: async () => {
                set({ isLoading: true });
                try {
                    const response = await cartApi.get();
                    if (response.data) {
                        // Filter out groups with null sellers to prevent rendering errors
                        const itemsBySeller = (response.data.items_by_seller || [])
                            .filter((group: CartBySeller) => group.seller != null);
                        const allSellerIds = itemsBySeller.map((g: CartBySeller) => g.seller?.id).filter((id: number | undefined): id is number => id != null);
                        // Preserve user's seller selection: only auto-select all on first load
                        const currentSelected = get().selectedSellers;
                        let nextSelected: number[];
                        if (currentSelected.length === 0) {
                            // First load or empty selection — select all
                            nextSelected = allSellerIds;
                        } else {
                            // Keep only sellers that still exist in cart
                            nextSelected = currentSelected.filter(id => allSellerIds.includes(id));
                            // If all were removed, re-select all
                            if (nextSelected.length === 0) nextSelected = allSellerIds;
                        }
                        set({
                            items: response.data.items || [],
                            itemsBySeller,
                            itemCount: response.data.item_count || 0,
                            total: response.data.total || 0,
                            selectedSellers: nextSelected,
                        });
                    }
                } catch (error) {
                    console.error('Failed to fetch cart:', error);
                } finally {
                    set({ isLoading: false });
                }
            },

            addItem: async (offerId, quantity = 1) => {
                set({ isLoading: true });
                try {
                    const response = await cartApi.addItem(offerId, quantity);
                    if (response.data) {
                        set({
                            itemCount: response.data.item_count,
                            total: response.data.total,
                        });
                        // Trigger shake animation
                        get().triggerShake();
                        // Refetch full cart to get updated items
                        await get().fetchCart();
                        // Set last added item for highlight
                        if (response.data.item) {
                            get().setLastAddedItemId(response.data.item.id);
                        }
                    } else if (response.error) {
                        throw new Error(response.error);
                    }
                } catch (error) {
                    console.error('Failed to add item:', error);
                    throw error;
                } finally {
                    set({ isLoading: false });
                }
            },

            updateQuantity: async (itemId, quantity) => {
                // Optimistic local update
                const prevItems = get().items;
                const prevBySeller = get().itemsBySeller;
                const prevCount = get().itemCount;
                const prevTotal = get().total;

                const updatedItems = prevItems.map(item =>
                    item.id === itemId ? { ...item, quantity } : item
                );
                const updatedBySeller = prevBySeller.map(group => ({
                    ...group,
                    items: group.items.map(item =>
                        item.id === itemId ? { ...item, quantity } : item
                    ),
                    subtotal: group.items.reduce((sum, item) =>
                        sum + (item.id === itemId ? item.price_at_addition * quantity : item.price_at_addition * item.quantity), 0
                    ),
                }));
                const optimisticTotal = updatedItems.reduce((sum, item) => sum + item.price_at_addition * item.quantity, 0);

                set({
                    items: updatedItems,
                    itemsBySeller: updatedBySeller,
                    total: optimisticTotal,
                });

                try {
                    const response = await cartApi.updateQuantity(itemId, quantity);
                    if (response.data) {
                        set({
                            itemCount: response.data.item_count,
                            total: response.data.total,
                        });
                        // Update the specific item from response if returned
                        if (response.data.item) {
                            const serverItem = response.data.item;
                            set({
                                items: get().items.map(item =>
                                    item.id === itemId ? { ...item, ...serverItem, quantity } : item
                                ),
                            });
                        }
                    } else if (response.error) {
                        // Rollback on error
                        set({ items: prevItems, itemsBySeller: prevBySeller, itemCount: prevCount, total: prevTotal });
                        throw new Error(response.error);
                    }
                } catch (error) {
                    // Rollback on network error
                    if (get().items !== prevItems) {
                        set({ items: prevItems, itemsBySeller: prevBySeller, itemCount: prevCount, total: prevTotal });
                    }
                    console.error('Failed to update quantity:', error);
                    throw error;
                }
            },

            removeItem: async (itemId) => {
                // Set removing animation
                get().setRemovingItemId(itemId);

                // Wait for animation
                await new Promise(resolve => setTimeout(resolve, 300));

                set({ isLoading: true });
                try {
                    const response = await cartApi.removeItem(itemId);
                    if (response.data) {
                        // Remove item locally instead of refetching
                        const updatedItems = get().items.filter(item => item.id !== itemId);
                        const updatedBySeller = get().itemsBySeller
                            .map(group => ({
                                ...group,
                                items: group.items.filter(item => item.id !== itemId),
                                subtotal: group.items
                                    .filter(item => item.id !== itemId)
                                    .reduce((sum, item) => sum + item.price_at_addition * item.quantity, 0),
                            }))
                            .filter(group => group.items.length > 0);

                        set({
                            items: updatedItems,
                            itemsBySeller: updatedBySeller,
                            itemCount: response.data.item_count,
                            total: response.data.total,
                        });
                    }
                } catch (error) {
                    console.error('Failed to remove item:', error);
                    // Refetch to resync on error
                    await get().fetchCart();
                    throw error;
                } finally {
                    set({ isLoading: false });
                    get().setRemovingItemId(null);
                }
            },

            clearCart: async () => {
                set({ isLoading: true });
                try {
                    await cartApi.clear();
                    set({
                        items: [],
                        itemsBySeller: [],
                        itemCount: 0,
                        total: 0,
                    });
                } catch (error) {
                    console.error('Failed to clear cart:', error);
                    throw error;
                } finally {
                    set({ isLoading: false });
                }
            },

            validateCart: async () => {
                try {
                    const response = await cartApi.validate();
                    if (response.data) {
                        set({ validationIssues: response.data.issues || [] });
                        return response.data.valid;
                    }
                    return false;
                } catch (error) {
                    console.error('Failed to validate cart:', error);
                    return false;
                }
            },
        }),
        {
            name: 'cart-storage',
            partialize: (state) => ({
                itemCount: state.itemCount,
                total: state.total,
                selectedSellers: state.selectedSellers,
            }),
        }
    )
);
