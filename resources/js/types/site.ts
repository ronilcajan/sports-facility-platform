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
};

export type PublicCourt = {
    id: number;
    name: string;
    slug: string;
    sport_type: string;
    description: string | null;
    base_price: string;
    slot_duration_minutes: number;
    primary_image_url?: string | null;
};
