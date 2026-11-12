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
    date_summary: string;
    date_mode: string;
    event_month: string | null;
    guests: number | null;
    guest_range: string | null;
    guest_range_label: string | null;
    source: string;
    source_label: string;
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

export type CustomerTag = {
    id: number;
    name: string;
    slug: string;
    color: string;
};

export type CustomerCard = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    whatsapp_phone: string | null;
    notes: string | null;
    assigned_to: number | null;
    assignee: string | null;
    email_opt_out_at: string | null;
    pipeline_stage: string;
    pipeline_stage_label: string;
    is_archived: boolean;
    is_anonymized: boolean;
    inquiries_count: number;
    accepted_inquiries_count: number;
    open_tasks_count: number;
    overdue_tasks_count: number;
    tags: CustomerTag[];
    created_at: string | null;
    show_url: string;
};

export type BookingContractCard = {
    id: number;
    title: string;
    body: string;
    status: string;
    status_label: string;
    sent_at: string | null;
    accepted_at: string | null;
    accepted_by_name: string | null;
    can_accept: boolean;
    can_edit: boolean;
    update_url: string;
    publish_url: string;
    public_url: string;
    accept_url: string;
};

export type InquiryDetail = InquiryCard & {
    whatsapp_phone: string | null;
    event_type: string | null;
    message: string | null;
    source: string;
    source_detail: string | null;
    utm_source: string | null;
    utm_medium: string | null;
    utm_campaign: string | null;
    utm_term: string | null;
    utm_content: string | null;
    referrer: string | null;
    portal_url: string;
    customer_confirmed_at: string | null;
    feedback_rating: number | null;
    feedback_comment: string | null;
    feedback_at: string | null;
    contract: BookingContractCard | null;
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

export type QuoteItem = {
    id: number;
    type: 'rental' | 'service' | 'package' | 'product' | 'custom';
    type_label: string;
    description: string;
    details: string | null;
    unit: string;
    unit_label: string;
    quantity: number;
    unit_price_cents: number;
    amount_cents: number;
    unit_price_formatted: string;
    amount_formatted: string;
    sort_order: number;
};

export type QuoteHistoryEntry = {
    id: number;
    from_status: string | null;
    from_status_label: string | null;
    to_status: string;
    to_status_label: string | null;
    note: string | null;
    changed_by: string | null;
    created_at: string | null;
};

export type CustomerQuoteCard = {
    id: number;
    folio: string;
    status: string;
    status_label: string;
    start_date: string;
    end_date: string;
    start_date_label: string;
    end_date_label: string;
    rental_mode: 'hour' | 'day' | 'fixed';
    start_time: string | null;
    end_time: string | null;
    billing_quantity: number | null;
    event_type: string | null;
    guests: number | null;
    subtotal_cents: number;
    tax_base_cents: number;
    discount_type: 'none' | 'fixed' | 'percent';
    discount_value: number;
    discount_cents: number;
    tax_mode: 'none' | 'added' | 'included';
    tax_rate_bps: number;
    tax_cents: number;
    total_cents: number;
    deposit_cents: number;
    subtotal_formatted: string;
    discount_formatted: string;
    tax_base_formatted: string;
    tax_formatted: string;
    total_formatted: string;
    deposit_formatted: string;
    currency: string;
    notes: string | null;
    terms: string | null;
    expires_at: string | null;
    issued_at: string | null;
    pdf_generated_at: string | null;
    sent_at: string | null;
    accepted_at: string | null;
    declined_at: string | null;
    cancelled_at: string | null;
    converted_inquiry_id: number | null;
    items: QuoteItem[];
    history: QuoteHistoryEntry[];
    customer: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
    };
    listing: {
        id: number;
        name: string;
        slug: string;
        logo_url: string | null;
        address: string | null;
        city: string | null;
        state: string | null;
        contact_email: string | null;
        contact_phone: string | null;
    };
    public_url: string;
    show_url: string;
    edit_url: string;
    update_url: string;
    preview_url: string;
    pdf_url: string;
    send_url: string;
    convert_url: string;
    issue_url: string;
    duplicate_url: string;
    cancel_url: string;
    can_edit: boolean;
    can_issue: boolean;
    can_send: boolean;
    can_convert: boolean;
    can_duplicate: boolean;
    can_cancel: boolean;
    can_download_pdf: boolean;
    created_at: string | null;
};

export type QuotationListing = {
    id: number;
    name: string;
    slug: string;
    logo_url: string | null;
    address: string | null;
    city: string | null;
    state: string | null;
    contact_email: string | null;
    contact_phone: string | null;
    timezone: string;
    price_from_cents: number | null;
    event_types: string[];
    services: Array<{
        id: number;
        key: string;
        label: string;
        description: string | null;
        price_cents: number;
        price_unit: string;
        unit_label: string;
        is_included: boolean;
        pricing_mode: string;
    }>;
    packages: Array<{
        id: number;
        name: string;
        description: string | null;
        price_cents: number;
        duration_hours: number;
        included_people: number;
        conditions: string | null;
        services: Array<{ key: string; label: string }>;
    }>;
};

export type QuotationCustomerOption = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
};

export type CustomerActivity = {
    id: number;
    type: string;
    type_label: string;
    body: string;
    author: string | null;
    occurred_at: string;
    can_delete: boolean;
    delete_url: string;
};

export type CustomerTask = {
    id: number;
    title: string;
    notes: string | null;
    due_at: string | null;
    priority: string;
    priority_label: string;
    status: string;
    status_label: string;
    assignee: string | null;
    assignee_id: number | null;
    is_overdue: boolean;
    can_manage: boolean;
    update_url: string;
    complete_url: string;
    cancel_url: string;
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
