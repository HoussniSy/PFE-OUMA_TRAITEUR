// Types pour les documents (devis et factures)

export type DocumentType = 'quote' | 'invoice';
export type DocumentStatus = 'draft' | 'sent' | 'partially_paid' | 'paid' | 'cancelled';

export interface DocumentItem {
  id?: number;
  designation: string;
  numberOfDays: number;
  numberOfPersons: number;
  numberOfServices: number;
  unitPrice: string;
  totalAmount: string;
  position: number;
  category?: string;
}

export interface Document {
  id: number;
  type: DocumentType;
  typeLabel: string;
  number: string;
  date: string;
  dueDate?: string;
  status: DocumentStatus;
  statusLabel: string;
  location?: string;
  totalHt: string;
  taxRate: string;
  totalTtc: string;
  currency: string;
  paymentTerms: number;
  clientId: number;
  clientName: string;
  clientEmail?: string;
  clientPhone?: string;
  clientAddress?: string;
  items: DocumentItem[];
  payments: Payment[];
  totalPaid: number;
  remainingAmount: number;
  isFullyPaid: boolean;
  isOverdue: boolean;
  daysOverdue: number;
  createdAt: string;
}

export interface DocumentListItem {
  id: number;
  type: DocumentType;
  typeLabel: string;
  number: string;
  date: string;
  dueDate?: string;
  status: DocumentStatus;
  statusLabel: string;
  totalHt: string;
  taxRate: string;
  totalTtc: string;
  currency: string;
  clientId: number;
  clientName: string;
  totalPaid: number;
  remainingAmount: number;
  isOverdue: boolean;
  createdAt: string;
}

export interface CreateDocumentData {
  type: DocumentType;
  clientId: number;
  date?: string;
  location?: string;
  taxRate?: string;
  currency?: string;
  paymentTerms?: number;
  status?: DocumentStatus;
  items: Omit<DocumentItem, 'id' | 'totalAmount'>[];
}

export interface Payment {
  id: number;
  datePaiement: string;
  montant: string;
  modePaiement: string;
  modePaiementLabel: string;
  statutPaiement: string;
  statutLabel: string;
  reference?: string;
  notes?: string;
  documentId?: number;
  documentNumber?: string;
  documentType?: string;
  clientName?: string;
  createdAt: string;
}

export interface CreatePaymentData {
  documentId: number;
  montant: string;
  modePaiement?: string;
  statutPaiement?: string;
  datePaiement?: string;
  reference?: string;
  notes?: string;
}

// Réponse API paginée
export interface PaginatedResponse<T> {
  data: T[];
  total: number;
  page: number;
  totalPages: number;
}
