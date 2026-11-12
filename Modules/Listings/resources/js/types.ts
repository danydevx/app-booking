export type ListingStatus = 'draft' | 'published' | 'suspended';

export type ServiceOption = {
    value: string;
    label: string;
};

export type ServiceGroup = {
    key: string;
    label: string;
    services: ServiceOption[];
};

export type ListingDetailField = {
    label: string;
    type: 'text' | 'select';
    placeholder?: string;
    options?: Record<string, string>;
};

export type ListingService = {
    key: string;
    label: string;
};

export type ListingPricing = {
    has_pricing: boolean;
    currency: string;
    price_from_cents: number | null;
    price_from_formatted: string | null;
    daily_prices: Array<{
        key: string;
        label: string;
        cents: number | null;
        formatted: string | null;
        price: string | null;
        hours: number | null;
        entry_time: string | null;
        entry_time_label: string | null;
        exit_time: string | null;
        exit_time_label: string | null;
        has_data: boolean;
    }>;
    included_hours: number | null;
    included_people: number | null;
    extra_hour_formatted: string | null;
    extra_person_formatted: string | null;
    deposit_required_formatted: string | null;
    price_notes: string | null;
};

export type ListingPackagePublic = {
    id: number;
    name: string;
    description: string | null;
    price_formatted: string;
    currency: string;
    duration_hours: number;
    included_people: number;
    services: Array<{ key: string; label: string }>;
    conditions: string | null;
};

export type ListingCommercialService = {
    id: number;
    service_key: string;
    label: string;
    description: string | null;
    is_included: boolean;
    pricing_mode: string;
    pricing_mode_label: string;
    provider: string;
    provider_label: string;
    price_formatted: string | null;
    has_price: boolean;
    price_unit: string;
    price_unit_label: string;
    currency: string;
};

export type ListingPromotionPublic = {
    id: number;
    title: string;
    description: string | null;
    promotional_price_formatted: string | null;
    previous_price_formatted: string | null;
    currency: string;
    starts_at: string;
    ends_at: string;
    starts_at_label: string;
    ends_at_label: string;
    applies_on: string[];
    conditions: string | null;
};

export type ListingPriceRangePublic = {
    id: number;
    name: string;
    starts_at: string;
    ends_at: string;
    starts_at_label: string;
    ends_at_label: string;
    price_formatted: string;
    currency: string;
    daily_pricing: Array<{
        key: string;
        label: string;
        cents: number | null;
        formatted: string | null;
        price: string | null;
        hours: number | null;
        entry_time: string | null;
        entry_time_label: string | null;
        exit_time: string | null;
        exit_time_label: string | null;
        has_data: boolean;
    }>;
    included_hours: number | null;
    included_people: number | null;
    extra_hour_formatted: string | null;
    extra_person_formatted: string | null;
    deposit_required_formatted: string | null;
    conditions: string | null;
    status: { value: string; label: string };
};

export type ListingLimits = {
    max_images: number;
    max_videos: number;
    max_image_kb: number;
    max_video_kb: number;
};

export type LocationCountry = {
    value: string;
    label: string;
};

export type ListingLocations = Record<
    string,
    {
        label: string;
        municipalities: string[];
    }
>;

export type ListingMedia = {
    id: number;
    type: 'image' | 'video';
    type_label: string;
    url: string;
    mime_type: string;
    alt_text: string | null;
    sort_order: number;
    delete_url?: string;
};

export type ListingFormData = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    address: string | null;
    city: string | null;
    state: string | null;
    country: string | null;
    postal_code: string | null;
    latitude: string | number | null;
    longitude: string | number | null;
    capacity: number | null;
    services: string[];
    details: Record<string, string | null>;
    rules: string | null;
    contact_name: string | null;
    contact_email: string | null;
    contact_phone: string | null;
    whatsapp_phone: string | null;
    status: ListingStatus;
    status_label: string;
    published_at: string | null;
    public_url: string | null;
    media: ListingMedia[];
};

export type ListingOwnerCard = {
    id: number;
    name: string;
    slug: string;
    status: ListingStatus;
    status_label: string;
    city: string | null;
    state: string | null;
    cover_url: string | null;
    images_count: number;
    videos_count: number;
    public_url: string | null;
    manage_url: string;
    edit_url: string;
    updated_at: string | null;
};

export type ListingPublicCard = {
    id: number;
    name: string;
    slug: string;
    city: string | null;
    state: string | null;
    capacity: number | null;
    services: string[];
    cover_url: string | null;
    price_from_formatted: string | null;
    latitude: number | null;
    longitude: number | null;
    distance_km: number | null;
    url: string;
};

export type ListingPublicDetail = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    address: string | null;
    city: string | null;
    state: string | null;
    country: string | null;
    postal_code: string | null;
    latitude: number | null;
    longitude: number | null;
    capacity: number | null;
    services: ListingService[];
    serviceGroups: Array<{
        key: string;
        label: string;
        services: ServiceOption[];
    }>;
    pricing: ListingPricing;
    priceRanges: ListingPriceRangePublic[];
    packages: ListingPackagePublic[];
    commercialServices: ListingCommercialService[];
    promotions: ListingPromotionPublic[];
    details: Array<{ key: string; label: string; value: string }>;
    rules: string | null;
    contact_name: string | null;
    contact_email: string | null;
    contact_phone: string | null;
    whatsapp_url: string | null;
    account: {
        name: string | null;
        logo_url: string | null;
        contact_email: string | null;
        contact_phone: string | null;
        whatsapp_url: string | null;
    };
    timezone: string;
    url: string;
    media: ListingMedia[];
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

export type PublicFilters = {
    q?: string | null;
    state?: string | null;
    city?: string | null;
    postal_code?: string | null;
    capacity?: string | number | null;
    service?: string | null;
    price_min?: string | number | null;
    price_max?: string | number | null;
    lat?: string | number | null;
    lng?: string | number | null;
    radius?: string | number | null;
    north?: string | number | null;
    south?: string | number | null;
    east?: string | number | null;
    west?: string | number | null;
};

export type GeoBounds = {
    north: number;
    south: number;
    east: number;
    west: number;
};

export type GeoMapPoint = {
    id: number;
    name: string;
    slug: string;
    city: string | null;
    state: string | null;
    capacity: number | null;
    price_from_formatted: string | null;
    cover_url: string | null;
    latitude: number | null;
    longitude: number | null;
    url: string;
};

export type LocationSuggestion = {
    type: 'city' | 'state' | 'postal_code';
    label: string;
    sublabel: string;
    params: Record<string, string>;
};
