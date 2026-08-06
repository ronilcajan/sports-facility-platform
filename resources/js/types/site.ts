export type SiteLink = {
    label: string;
    href: string;
};

export type SiteContact = {
    email: string;
    phone: string;
    address_line: string;
    maps_query: string;
};

export type SiteHours = {
    day: string;
    value: string;
};

export type SiteSocial = {
    label: string;
    url: string;
};

export type SiteData = {
    name: string;
    tagline: string;
    description: string;
    contact: SiteContact;
    hours: SiteHours[];
    social: SiteSocial[];
    nav: SiteLink[];
    legal: SiteLink[];
    logo: string;
};

export type PaymentMethod = {
    number: string;
    qr_url?: string | null;
};

export type VenuePaymentMethods = {
    gcash?: PaymentMethod;
    maya?: PaymentMethod;
};

export type PublicVenue = {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    address?: string | null;
    phone?: string | null;
    email?: string | null;
    image_url?: string | null;
    payment_methods?: VenuePaymentMethods;
};

export type PublicCourt = {
    id: number;
    venue_id?: number | null;
    venue?: PublicVenue | null;
    name: string;
    slug: string;
    sport_type: string;
    description: string | null;
    base_price: string;
    slot_prices?: Record<string, string | number> | null;
    slot_duration_minutes: number;
    primary_image_url?: string | null;
};
