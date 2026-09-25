export type PaginatedResponse<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

export type IndexRoutes = {
    index: string;
    create: string;
    show: string;
    changeVisibility: string;
    destroy: string;
};

export type DetailsRoutes = {
    index?: string;
    store?: string;
    update?: string;
    requestGatewayInvoice?: string;
    municipalOptions?: string;
    municipalServices?: string;
    municipalConfiguration?: string;
    imageRights?: string;
};
