import { usePage } from '@inertiajs/vue3';
import { computed, type ComputedRef } from 'vue';
import type { SiteData } from '@/types';

/**
 * Typed access to the globally-shared public-site identity, navigation, and
 * contact details (provided by HandleInertiaRequests::siteData).
 */
export function useSite(): ComputedRef<SiteData> {
    const page = usePage();

    return computed(() => page.props.site as SiteData);
}
