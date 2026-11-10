export type InquiryStatus =
    | 'new'
    | 'contacted'
    | 'accepted'
    | 'declined'
    | 'expired'
    | 'cancelled';

export type DepositStatus = 'pending' | 'received' | 'refunded' | 'cancelled';
export type DepositMethod = 'cash' | 'transfer' | 'other';

export type StatusOption = {
    value: string;
    label: string;
};

export type BookingListing = {
    id: number;
    name: string;
    slug: string;
};

export type InquiryCard = {
    id: number;
    name: string;
    email: string;
    phone: string;
    start_date: string;
    end_date: string;
    start_date_label: string;
    end_date_label: string;
    guests: number | null;
    status: InquiryStatus;
    status_label: string;
    created_at: string | null;
    listing: BookingListing;
    customer_id: number | null;
    show_url: string;
};

export type BookingDeposit = {
    id: number;
    amount: number;
    amount_formatted: string;
    currency: string;
    status: DepositStatus;
    status_label: string;
    method: DepositMethod;
    method_label: string;
    reference: string | null;
    received_at: string | null;
    notes: string | null;
    update_url: string;
};

export type CustomerCard = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    whatsapp_phone: string | null;
    notes: string | null;
    inquiries_count: number;
    created_at: string | null;
    show_url: string;
};

export type InquiryDetail = InquiryCard & {
    whatsapp_phone: string | null;
    event_type: string | null;
    message: string | null;
    source: string;
    consent_at: string | null;
    contacted_at: string | null;
    accepted_at: string | null;
    declined_at: string | null;
    cancelled_at: string | null;
    public_listing_url: string;
    whatsapp_url: string | null;
    customer: CustomerCard | null;
    deposits: BookingDeposit[];
};

export type CustomerDetail = CustomerCard & {
    inquiries: InquiryCard[];
};

export type BookingActivity = {
    id: number;
    event: string;
    label: string;
    summary: string | null;
    causer: string | null;
    created_at: string | null;
};

export type BlockedDate = {
    date: string;
    status: 'blocked' | 'held' | 'booked';
    label: string;
    reason: string | null;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
};

export type PublicListingSummary = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    city: string | null;
    state: string | null;
    address: string | null;
    capacity: number | null;
    services: Array<{ key: string; label: string }>;
    media: Array<{
        id: number;
        type: 'image' | 'video';
        url: string;
        alt_text: string | null;
    }>;
};
