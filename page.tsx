"use client"

import { useState, useEffect } from "react"
import { useParams } from "next/navigation"
import Image from "next/image"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { Card, CardContent } from "@/components/ui/card"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Textarea } from "@/components/ui/textarea"
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog"
import { 
  Send, 
  Printer, 
  Download,
  CheckCircle,
  Loader2,
  Mail,
  Phone,
  Calendar,
  Plus,
  X,
  MessageSquare,
  DollarSign,
  FileSpreadsheet,
  Upload
} from "lucide-react"
import { format } from "date-fns"
import { tr } from "date-fns/locale"

interface OfferItem {
  id: string
  productCode: string | null
  productName: string
  quantity: number
  unit: string
  unitPrice: number
  taxRate: number
}

interface OfferData {
  offer: {
    id: string
    offerNumber: string
    customerName: string
    customerEmail: string | null
    customerPhone: string | null
    validUntil: Date | null
    items: OfferItem[]
  }
  organization: {
    id: string
    name: string
    logo: string | null
  }
  supplier: {
    companyName: string
  }
  status: string
}

// Payment method types
type PaymentMethod = 'cash' | 'credit_card' | 'installment_7' | 'installment_10' | 'installment_15' | 'installment_30' | 'installment_45' | 'installment_60' | 'installment_90' | 'installment_120' | 'installment_150'

const PAYMENT_METHODS: { value: PaymentMethod; label: string }[] = [
  { value: 'cash', label: 'Peşin' },
  { value: 'credit_card', label: 'Kredi Kartı' },
  { value: 'installment_7', label: 'Vadeli 7' },
  { value: 'installment_10', label: 'Vadeli 10' },
  { value: 'installment_15', label: 'Vadeli 15' },
  { value: 'installment_30', label: 'Vadeli 30' },
  { value: 'installment_45', label: 'Vadeli 45' },
  { value: 'installment_60', label: 'Vadeli 60' },
  { value: 'installment_90', label: 'Vadeli 90' },
  { value: 'installment_120', label: 'Vadeli 120' },
  { value: 'installment_150', label: 'Vadeli 150' }
]

interface PaymentOffer {
  method: PaymentMethod
  currency: string
  unitPrice: string
  taxRate: string
  extraCharge: string
  deliveryTerm: string
  validityDate: string
  note: string
}

// Helper function to get initials from company name
function getCompanyInitials(name: string): string {
  const words = name.split(' ')
  if (words.length >= 2) {
    return (words[0][0] + words[1][0]).toUpperCase()
  }
  return name.substring(0, 2).toUpperCase()
}

// Helper function to get currency symbol
function getCurrencySymbol(currency: string): string {
  switch (currency) {
    case 'USD':
      return '$'
    case 'EUR':
      return '€'
    case 'TRY':
      return '₺'
    default:
      return currency
  }
}

export default function SupplierOfferResponsePage() {
  const params = useParams()
  const offerId = params.id as string
  const supplierId = params.supplierId as string

  const [loading, setLoading] = useState(true)
  const [submitting, setSubmitting] = useState(false)
  const [offerData, setOfferData] = useState<OfferData | null>(null)
  const [submitted, setSubmitted] = useState(false)
  
  // Payment methods state
  const [preferredPaymentMethod, setPreferredPaymentMethod] = useState<PaymentMethod>('cash')
  const [alternativePayments, setAlternativePayments] = useState<PaymentOffer[]>([])
  const [showPaymentModal, setShowPaymentModal] = useState(false)
  
  // Item prices - each item has its own price (from Fiyat column) and KDV column price
  const [itemPrices, setItemPrices] = useState<Record<string, { unitPrice: string; kdvPrice: string; taxRate: string }>>({})
  
  // Current payment offer (for cash/default) - without unitPrice (moved to table)
  const [currentOffer, setCurrentOffer] = useState<PaymentOffer>({
    method: 'cash',
    currency: 'TRY',
    unitPrice: '', // Not used anymore, kept for compatibility
    taxRate: '20', // Default tax rate, can be overridden per item
    extraCharge: '0',
    deliveryTerm: '',
    validityDate: format(new Date(Date.now() + 30 * 24 * 60 * 60 * 1000), 'yyyy-MM-dd'),
    note: ''
  })

  // Show payment details section
  const [showPaymentDetails, setShowPaymentDetails] = useState(false)

  // Exchange rates
  const [exchangeRates, setExchangeRates] = useState<{
    USD: { buying: number; selling: number }
    EUR: { buying: number; selling: number }
  }>({
    USD: { buying: 0, selling: 0 },
    EUR: { buying: 0, selling: 0 }
  })

  useEffect(() => {
    if (offerId && supplierId) {
      fetch(`/api/offers/${offerId}/supplier/${supplierId}`)
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            setOfferData(data.data)
            setSubmitted(data.data.status === 'responded')
            
            // Initialize item prices
            const initialPrices: Record<string, { unitPrice: string; kdvPrice: string; taxRate: string }> = {}
            data.data.offer.items.forEach((item: OfferItem) => {
              initialPrices[item.id] = {
                unitPrice: item.unitPrice > 0 ? item.unitPrice.toString() : '',
                kdvPrice: item.unitPrice > 0 ? item.unitPrice.toString() : '', // Same as unitPrice initially
                taxRate: item.taxRate.toString()
              }
            })
            setItemPrices(initialPrices)
            
            // Set default tax rate
            if (data.data.offer.items.length > 0) {
              const firstItem = data.data.offer.items[0]
              setCurrentOffer(prev => ({
                ...prev,
                taxRate: firstItem.taxRate.toString()
              }))
            }
          }
          setLoading(false)
        })
        .catch(err => {
          console.error(err)
          setLoading(false)
        })
    }
  }, [offerId, supplierId])

  // Fetch exchange rates
  useEffect(() => {
    fetch('/api/exchange-rates')
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          setExchangeRates(data.data)
        }
      })
      .catch(err => console.error('Error fetching exchange rates:', err))
  }, [])

  const calculateTotals = () => {
    if (!offerData) return { subtotal: 0, taxAmount: 0, extraCharge: 0, total: 0, kdvSubtotal: 0, kdvTotal: 0 }
    
    const extraCharge = parseFloat(currentOffer.extraCharge || '0')
    
    // Calculate subtotal and tax for all items
    let subtotal = 0 // KDV'siz toplam (Fiyat sütunundan)
    let taxAmount = 0
    let kdvSubtotal = 0 // KDV'siz toplam (KDV sütunundan)
    let kdvTotal = 0 // KDV'li toplam (KDV sütunundan)
    
    offerData.offer.items.forEach(item => {
      const price = itemPrices[item.id]
      if (price) {
        const unitPrice = parseFloat(price.unitPrice || '0') // Fiyat sütunundan
        const kdvPrice = parseFloat(price.kdvPrice || price.unitPrice || '0') // KDV sütunundan
        const taxRate = parseFloat(price.taxRate || currentOffer.taxRate || '20')
        const quantity = Number(item.quantity)
        
        // Fiyat sütunundan hesaplama
        const itemSubtotal = unitPrice * quantity
        const itemTax = itemSubtotal * (taxRate / 100)
        
        // KDV sütunundan hesaplama
        const kdvItemSubtotal = kdvPrice * quantity
        const kdvItemTax = kdvItemSubtotal * (taxRate / 100)
        const kdvItemTotal = kdvItemSubtotal + kdvItemTax
        
        subtotal += itemSubtotal
        taxAmount += itemTax
        kdvSubtotal += kdvItemSubtotal
        kdvTotal += kdvItemTotal
      }
    })
    
    const total = subtotal + taxAmount + extraCharge

    return {
      subtotal,
      taxAmount,
      extraCharge,
      total,
      kdvSubtotal,
      kdvTotal
    }
  }

  const handleAddAlternativePayment = () => {
    setShowPaymentModal(true)
  }

  const handleSelectPaymentMethod = (method: PaymentMethod) => {
    setShowPaymentModal(false)
    
    // Add new alternative payment
    const newPayment: PaymentOffer = {
      method,
      currency: 'TRY',
      unitPrice: '',
      taxRate: '20',
      extraCharge: '0',
      deliveryTerm: '',
      validityDate: format(new Date(Date.now() + 30 * 24 * 60 * 60 * 1000), 'yyyy-MM-dd'),
      note: ''
    }
    setAlternativePayments([...alternativePayments, newPayment])
    setShowPaymentDetails(true)
  }

  const handleRemoveAlternativePayment = (index: number) => {
    setAlternativePayments(alternativePayments.filter((_, i) => i !== index))
  }

  const handleSubmit = async () => {
    if (!offerData) return

    // Check if all items have prices (both Fiyat and KDV columns)
    const hasAllPrices = offerData.offer.items.every(item => {
      const price = itemPrices[item.id]
      return price && price.unitPrice && parseFloat(price.unitPrice) > 0 && 
             price.kdvPrice && parseFloat(price.kdvPrice) > 0
    })

    if (!hasAllPrices || !currentOffer.deliveryTerm || !currentOffer.validityDate) {
      alert('Lütfen tüm ürünler için fiyat girin ve zorunlu alanları doldurun (Termin, Geçerlilik Tarihi)')
      return
    }

    setSubmitting(true)

    // Prepare items for submission - each item has its own price and tax rate
    const items = offerData.offer.items.map(item => {
      const price = itemPrices[item.id]
      return {
        itemId: item.id,
        unitPrice: price?.unitPrice || '0',
        kdvPrice: price?.kdvPrice || price?.unitPrice || '0', // KDV sütunundan gelen fiyat
        taxRate: price?.taxRate || currentOffer.taxRate || '20',
        paymentMethod: preferredPaymentMethod,
        currency: currentOffer.currency,
        extraCharge: currentOffer.extraCharge,
        deliveryTerm: currentOffer.deliveryTerm,
        validityDate: currentOffer.validityDate,
        note: currentOffer.note
      }
    })

    try {
      const res = await fetch(`/api/offers/${offerId}/supplier/${supplierId}/respond`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
          items,
          alternativePayments: alternativePayments.map(alt => ({
            method: alt.method,
            unitPrice: alt.unitPrice,
            taxRate: alt.taxRate,
            currency: alt.currency,
            extraCharge: alt.extraCharge,
            deliveryTerm: alt.deliveryTerm,
            validityDate: alt.validityDate,
            note: alt.note
          }))
        })
      })

      const data = await res.json()

      if (data.success) {
        setSubmitted(true)
        setTimeout(() => {
          window.location.href = `/supplier/${supplierId}/register`
        }, 1500)
      } else {
        alert('Hata: ' + data.error)
      }
    } catch (error) {
      console.error(error)
      alert('Bir hata oluştu')
    } finally {
      setSubmitting(false)
    }
  }

  if (loading) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <Loader2 className="h-8 w-8 animate-spin text-[#f6b900]" />
      </div>
    )
  }

  if (!offerData) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gray-50">
        <p className="text-red-500">Teklif bulunamadı</p>
      </div>
    )
  }

  const totals = calculateTotals()
  const validUntilDate = offerData.offer.validUntil 
    ? format(new Date(offerData.offer.validUntil), 'dd-MM-yyyy', { locale: tr })
    : null

  // Calculate days until validity
  const daysUntilValidity = offerData.offer.validUntil
    ? Math.ceil((new Date(offerData.offer.validUntil).getTime() - new Date().getTime()) / (1000 * 60 * 60 * 24))
    : null

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Top Teal Bar */}
      <div className="bg-[#f6b900] h-2"></div>

      {/* Header */}
      <div className="bg-white border-b">
        <div className="max-w-4xl mx-auto px-6 py-4 flex items-center justify-between">
          {/* Organization Logo */}
          <div className="flex items-center gap-4">
            {offerData.organization.logo ? (
              <Image 
                src={offerData.organization.logo} 
                alt={offerData.organization.name} 
                width={120} 
                height={40}
                className="h-10 w-auto object-contain"
              />
            ) : (
              <div className="h-10 w-24 bg-[#f6b900] rounded flex items-center justify-center">
                <span className="text-black font-bold text-lg">
                  {getCompanyInitials(offerData.organization.name)}
                </span>
              </div>
            )}
          </div>
          <h1 className="text-4xl font-light text-gray-400">TEKLİF FORMU</h1>
          <div className="flex flex-col gap-2">
            <button className="w-10 h-10 bg-green-500 rounded flex items-center justify-center">
              <span className="text-white text-xs">📱</span>
            </button>
            <button className="w-10 h-10 bg-blue-500 rounded flex items-center justify-center">
              <Printer className="h-5 w-5 text-white" />
            </button>
            <button className="w-10 h-10 bg-yellow-500 rounded flex items-center justify-center">
              <Download className="h-5 w-5 text-white" />
            </button>
          </div>
        </div>
      </div>

      {/* Exchange Rates Widget */}
      <div className="bg-gradient-to-r from-blue-50 to-green-50 border-b">
        <div className="max-w-4xl mx-auto px-6 py-3">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-6">
              <span className="text-sm font-semibold text-gray-700">Güncel Kurlar (TCMB):</span>
              {exchangeRates.USD.selling > 0 && (
                <div className="flex items-center gap-2">
                  <span className="text-sm font-medium text-blue-600">$ USD</span>
                  <span className="text-sm text-gray-700">{exchangeRates.USD.selling.toFixed(4)} ₺</span>
                </div>
              )}
              {exchangeRates.EUR.selling > 0 && (
                <div className="flex items-center gap-2">
                  <span className="text-sm font-medium text-green-600">€ EUR</span>
                  <span className="text-sm text-gray-700">{exchangeRates.EUR.selling.toFixed(4)} ₺</span>
                </div>
              )}
            </div>
            <span className="text-xs text-gray-500">Satış Kuru</span>
          </div>
        </div>
      </div>

      {/* Action Buttons Bar */}
      <div className="bg-white border-b">
        <div className="max-w-4xl mx-auto px-6 py-3 flex justify-end gap-3">
          <Button variant="outline" size="sm" className="bg-green-500 hover:bg-green-600 text-white border-0">
            <Upload className="h-4 w-4 mr-2" />
            Belge Yükle
          </Button>
          <Button variant="outline" size="sm" className="bg-green-500 hover:bg-green-600 text-white border-0">
            <MessageSquare className="h-4 w-4 mr-2" />
            Mesaj Gönder
          </Button>
          <Button variant="outline" size="sm" className="bg-red-500 hover:bg-red-600 text-white border-0">
            <DollarSign className="h-4 w-4 mr-2" />
            Toplu Fiyat Ver
          </Button>
          <Button variant="outline" size="sm" className="bg-green-500 hover:bg-green-600 text-white border-0">
            <FileSpreadsheet className="h-4 w-4 mr-2" />
            Excel ile Fiyat Ver
          </Button>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-4xl mx-auto px-6 py-8">
        {/* Customer Info & Offer Details */}
        <div className="grid grid-cols-2 gap-6 mb-6">
          {/* Customer Info */}
          <div>
            <h2 className="font-bold text-lg mb-3">Teklif Talep Edilen Bilgileri:</h2>
            <div className="space-y-2 text-sm">
              <p><strong>Firma Adı:</strong> {offerData.organization.name}</p>
              {offerData.offer.customerEmail && (
                <p className="flex items-center gap-2">
                  <Mail className="h-4 w-4" />
                  <span>{offerData.offer.customerEmail}</span>
                </p>
              )}
              {offerData.offer.customerPhone && (
                <p className="flex items-center gap-2">
                  <Phone className="h-4 w-4" />
                  <span>{offerData.offer.customerPhone}</span>
                </p>
              )}
            </div>
          </div>

          {/* Offer Details */}
          <Card className="bg-gray-50">
            <CardContent className="p-4 space-y-2">
              <p className="text-sm">
                <strong>Teklif No:</strong> <span className="text-blue-600">{offerData.offer.offerNumber}</span>
              </p>
              <p className="text-sm">
                <strong>Teklif Tarihi:</strong> <span className="text-blue-600">
                  {format(new Date(), 'dd-MM-yyyy', { locale: tr })}
                </span>
              </p>
              {daysUntilValidity !== null && (
                <p className="text-sm">
                  <strong>Teklif Geçerlilik:</strong> <span className="text-blue-600">{daysUntilValidity} Gün</span>
                </p>
              )}
            </CardContent>
          </Card>
        </div>

        {/* Payment Methods Section */}
        <Card className="mb-6 border-0 shadow-sm">
          <CardContent className="p-6">
            <div className="grid grid-cols-2 gap-6">
              <div>
                <h3 className="font-semibold text-gray-800 mb-3">Firmanın Tercihi</h3>
                <button
                  onClick={() => setPreferredPaymentMethod('cash')}
                  className={`px-4 py-2 rounded-lg border-2 transition-all ${
                    preferredPaymentMethod === 'cash'
                      ? 'border-green-500 bg-green-50'
                      : 'border-gray-300 bg-white hover:border-green-300'
                  }`}
                >
                  <div className="flex items-center gap-2">
                    <div className={`w-4 h-4 rounded-full border-2 ${
                      preferredPaymentMethod === 'cash' ? 'border-green-500 bg-green-500' : 'border-gray-300'
                    } flex items-center justify-center`}>
                      {preferredPaymentMethod === 'cash' && (
                        <div className="w-2 h-2 rounded-full bg-white"></div>
                      )}
                    </div>
                    <span className="font-medium">Peşin</span>
                  </div>
                </button>
              </div>

              <div>
                <h3 className="font-semibold text-gray-800 mb-3">Alternatif Ödeme Yöntemleri</h3>
                {alternativePayments.length === 0 ? (
                  <p className="text-sm text-gray-500 mb-3">Henüz alternatif ödeme yöntemi eklenmedi</p>
                ) : (
                  <div className="space-y-2 mb-3">
                    {alternativePayments.map((alt, idx) => (
                      <div key={idx} className="flex items-center justify-between bg-gray-50 p-2 rounded">
                        <span className="text-sm">
                          {PAYMENT_METHODS.find(m => m.value === alt.method)?.label}
                        </span>
                        <Button
                          variant="ghost"
                          size="sm"
                          onClick={() => handleRemoveAlternativePayment(idx)}
                          className="h-6 w-6 p-0"
                        >
                          <X className="h-4 w-4" />
                        </Button>
                      </div>
                    ))}
                  </div>
                )}
                <Button
                  variant="outline"
                  size="sm"
                  onClick={handleAddAlternativePayment}
                  className="bg-green-500 hover:bg-green-600 text-white border-0"
                >
                  <Plus className="h-4 w-4 mr-2" />
                  Alternatif Fiyat Ekle
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Products Table */}
        <Card className="mb-6 border-0 shadow-sm">
          <CardContent className="p-0">
            <table className="w-full">
              <thead className="bg-gray-100">
                <tr>
                  <th className="px-4 py-3 text-left text-sm font-semibold">Ürün</th>
                  <th className="px-4 py-3 text-center text-sm font-semibold">Fiyat</th>
                  <th className="px-4 py-3 text-center text-sm font-semibold">KDV'li Fiyat</th>
                  <th className="px-4 py-3 text-center text-sm font-semibold">Adet</th>
                  <th className="px-4 py-3 text-center text-sm font-semibold">KDV Oranı</th>
                  <th className="px-4 py-3 text-center text-sm font-semibold">Tutar</th>
                </tr>
              </thead>
              <tbody>
                {offerData.offer.items.map((item, index) => {
                  const price = itemPrices[item.id]
                  const unitPrice = parseFloat(price?.unitPrice || '0') // Fiyat sütunundan
                  const kdvPrice = parseFloat(price?.kdvPrice || price?.unitPrice || '0') // KDV sütunundan
                  const taxRate = parseFloat(price?.taxRate || currentOffer.taxRate || '20')
                  const quantity = Number(item.quantity)
                  
                  // Fiyat sütunundan hesaplama
                  const subtotal = unitPrice * quantity
                  const taxAmount = subtotal * (taxRate / 100)
                  const total = subtotal + taxAmount
                  
                  // KDV sütunundan hesaplama
                  const kdvSubtotal = kdvPrice * quantity
                  const kdvTaxAmount = kdvSubtotal * (taxRate / 100)
                  const kdvTotal = kdvSubtotal + kdvTaxAmount

                  return (
                    <tr key={item.id} className={index % 2 === 0 ? 'bg-white' : 'bg-gray-50'}>
                      <td className="px-4 py-3">
                        <div>
                          <div className="font-medium">{item.productName}</div>
                          {item.productCode && (
                            <div className="text-xs text-gray-500">Kod: {item.productCode}</div>
                          )}
                        </div>
                      </td>
                      <td className="px-4 py-3 text-center">
                        {submitted ? (
                          <span className="font-medium">{unitPrice.toFixed(2)} {getCurrencySymbol(currentOffer.currency)}</span>
                        ) : (
                          <div className="flex flex-col gap-1 items-center">
                            <Input
                              type="number"
                              step="0.01"
                              value={price?.unitPrice || ''}
                              onChange={(e) => setItemPrices({
                                ...itemPrices,
                                [item.id]: {
                                  ...itemPrices[item.id],
                                  unitPrice: e.target.value,
                                  kdvPrice: itemPrices[item.id]?.kdvPrice || e.target.value,
                                  taxRate: itemPrices[item.id]?.taxRate || currentOffer.taxRate || '20'
                                }
                              })}
                              placeholder="0"
                              className="w-24 mx-auto text-center h-8"
                            />
                            <span className="text-xs text-gray-500">KDV'siz birim fiyat</span>
                          </div>
                        )}
                      </td>
                      <td className="px-4 py-3 text-center">
                        {unitPrice > 0 ? (
                          <span className="text-green-600 font-medium">
                            {(unitPrice * (1 + taxRate / 100)).toFixed(2)} {getCurrencySymbol(currentOffer.currency)}
                          </span>
                        ) : (
                          <span className="text-gray-400">-</span>
                        )}
                      </td>
                      <td className="px-4 py-3 text-center">{quantity}</td>
                      <td className="px-4 py-3 text-center">
                        {submitted ? (
                          <span>%{taxRate}</span>
                        ) : (
                          <Select
                            value={price?.taxRate || '20'}
                            onValueChange={(value: string) => setItemPrices({
                              ...itemPrices,
                              [item.id]: {
                                ...itemPrices[item.id],
                                unitPrice: itemPrices[item.id]?.unitPrice || '',
                                kdvPrice: itemPrices[item.id]?.kdvPrice || itemPrices[item.id]?.unitPrice || '',
                                taxRate: value
                              }
                            })}
                          >
                            <SelectTrigger className="w-20 mx-auto h-8">
                              <SelectValue placeholder="%20" />
                            </SelectTrigger>
                            <SelectContent>
                              <SelectItem value="1">%1</SelectItem>
                              <SelectItem value="10">%10</SelectItem>
                              <SelectItem value="20">%20</SelectItem>
                            </SelectContent>
                          </Select>
                        )}
                      </td>
                      <td className="px-4 py-3 text-center font-medium">
                        {total.toFixed(2)} {getCurrencySymbol(currentOffer.currency)}
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </CardContent>
        </Card>

        {/* Totals */}
        <div className="flex justify-end mb-6">
          <div className="w-80 space-y-2">
            <div className="flex justify-between text-sm">
              <span>Ara Toplam (KDV'siz)</span>
              <span>{totals.subtotal.toFixed(2)} {getCurrencySymbol(currentOffer.currency)}</span>
            </div>
            <div className="flex justify-between text-sm text-red-600">
              <span>Toplam KDV</span>
              <span>{totals.taxAmount.toFixed(2)} {getCurrencySymbol(currentOffer.currency)}</span>
            </div>
            <div className="bg-green-500 text-white font-bold p-3 rounded">
              <div className="flex justify-between">
                <span>Genel Toplam (KDV'li)</span>
                <span>{totals.total.toFixed(2)} {getCurrencySymbol(currentOffer.currency)}</span>
              </div>
              {currentOffer.currency !== 'TRY' && (
                <div className="flex justify-between text-sm font-normal mt-2 opacity-90">
                  <span>TRY Karşılığı:</span>
                  <span>
                    {currentOffer.currency === 'USD' && exchangeRates.USD.selling > 0 && 
                      `${(totals.total * exchangeRates.USD.selling).toFixed(2)} ₺`
                    }
                    {currentOffer.currency === 'EUR' && exchangeRates.EUR.selling > 0 && 
                      `${(totals.total * exchangeRates.EUR.selling).toFixed(2)} ₺`
                    }
                  </span>
                </div>
              )}
            </div>
          </div>
        </div>

        {/* Payment Details Form - Moved below table */}
        <Card className="mb-6 border-0 shadow-sm">
          <CardContent className="p-6">
            <div className="grid grid-cols-2 gap-4">
              <div>
                <Label htmlFor="currency" className="text-sm font-semibold">
                  Para Birimi (*)
                </Label>
                <Select
                  value={currentOffer.currency}
                  onValueChange={(value: string) => setCurrentOffer({ ...currentOffer, currency: value })}
                  disabled={submitted}
                >
                  <SelectTrigger className="mt-1 h-9">
                    <SelectValue placeholder="Seçiniz" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="TRY">TRY - Türk Lirası</SelectItem>
                    <SelectItem value="USD">USD - Dolar</SelectItem>
                    <SelectItem value="EUR">EUR - Euro</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div>
                <Label htmlFor="taxRate" className="text-sm font-semibold">
                  KDV (*)
                </Label>
                <Select
                  value={currentOffer.taxRate}
                  onValueChange={(newTaxRate: string) => {
                    setCurrentOffer({ ...currentOffer, taxRate: newTaxRate })
                    // Update all items that don't have custom tax rate
                    const updatedPrices = { ...itemPrices }
                    offerData?.offer.items.forEach(item => {
                      if (!updatedPrices[item.id] || updatedPrices[item.id].taxRate === currentOffer.taxRate) {
                        updatedPrices[item.id] = {
                          ...updatedPrices[item.id],
                          unitPrice: updatedPrices[item.id]?.unitPrice || '',
                          kdvPrice: updatedPrices[item.id]?.kdvPrice || updatedPrices[item.id]?.unitPrice || '',
                          taxRate: newTaxRate
                        }
                      }
                    })
                    setItemPrices(updatedPrices)
                  }}
                  disabled={submitted}
                >
                  <SelectTrigger className="mt-1 h-9">
                    <SelectValue placeholder="%20" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="1">%1</SelectItem>
                    <SelectItem value="10">%10</SelectItem>
                    <SelectItem value="20">%20</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div>
                <Label htmlFor="extraCharge" className="text-sm font-semibold">
                  Ekstra Ücret
                </Label>
                <Input
                  id="extraCharge"
                  type="number"
                  step="0.01"
                  value={currentOffer.extraCharge}
                  onChange={(e) => setCurrentOffer({ ...currentOffer, extraCharge: e.target.value })}
                  placeholder="0"
                  className="mt-1 h-9"
                  disabled={submitted}
                />
              </div>

              <div>
                <Label htmlFor="deliveryTerm" className="text-sm font-semibold">
                  Termin (*)
                </Label>
                <Select
                  value={currentOffer.deliveryTerm}
                  onValueChange={(value: string) => setCurrentOffer({ ...currentOffer, deliveryTerm: value })}
                  disabled={submitted}
                >
                  <SelectTrigger className="mt-1 h-9">
                    <SelectValue placeholder="Seçiniz" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="7">7 Gün</SelectItem>
                    <SelectItem value="14">14 Gün</SelectItem>
                    <SelectItem value="21">21 Gün</SelectItem>
                    <SelectItem value="30">30 Gün</SelectItem>
                    <SelectItem value="45">45 Gün</SelectItem>
                    <SelectItem value="60">60 Gün</SelectItem>
                    <SelectItem value="90">90 Gün</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              <div>
                <Label htmlFor="validityDate" className="text-sm font-semibold">
                  Teklif Geçerlilik Tarihi (*)
                </Label>
                <div className="relative mt-1">
                  <Input
                    id="validityDate"
                    type="date"
                    value={currentOffer.validityDate}
                    onChange={(e) => setCurrentOffer({ ...currentOffer, validityDate: e.target.value })}
                    className="pr-10 h-9"
                    disabled={submitted}
                  />
                  <Calendar className="absolute right-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" />
                </div>
              </div>

              <div className="col-span-2">
                <Label htmlFor="note" className="text-sm font-semibold">
                  Notunuz
                </Label>
                <Textarea
                  id="note"
                  value={currentOffer.note}
                  onChange={(e) => setCurrentOffer({ ...currentOffer, note: e.target.value })}
                  placeholder="Bu not alıcıya teklif notu olarak iletilir."
                  className="mt-1 min-h-[80px]"
                  disabled={submitted}
                />
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Terms */}
        <div className="mb-6 space-y-4 text-sm">
          <div>
            <h3 className="font-bold mb-2">Şartlar ve Koşullar:</h3>
            <p>Fiyatlarımız maktu olup verilen fiyatımız nakit ödemeleriniz için geçerlidir.</p>
          </div>
          <div>
            <h3 className="font-bold mb-2">Onay ve İşe Başlama:</h3>
            <p>Teklifimizi onaylamanızın ardından sözleşme yapılarak iş bitiş tarihi ve teslim şartları oluşturulacaktır.</p>
          </div>
        </div>

        {/* Submit Button */}
        {!submitted && (
          <div className="flex justify-center">
            <Button
              onClick={handleSubmit}
              disabled={submitting}
              className="bg-[#f6b900] hover:bg-[#e0a800] text-black font-bold px-8 py-6 text-lg"
            >
              {submitting ? (
                <>
                  <Loader2 className="mr-2 h-5 w-5 animate-spin" />
                  Gönderiliyor...
                </>
              ) : (
                <>
                  <Send className="mr-2 h-5 w-5" />
                  Fiyatını İlet
                </>
              )}
            </Button>
          </div>
        )}

        {submitted && (
          <div className="flex flex-col items-center gap-4">
            <div className="bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded flex items-center gap-2">
              <CheckCircle className="h-5 w-5" />
              <span className="font-semibold">Teklifiniz başarıyla gönderildi!</span>
            </div>
            <Button
              onClick={() => setSubmitted(false)}
              variant="outline"
              className="border-[#f6b900] text-[#f6b900] hover:bg-[#f6b900] hover:text-black"
            >
              Teklifi Tekrar Düzenle
            </Button>
          </div>
        )}

        {/* Footer - Teklif Up Branding */}
        <div className="mt-8 border-t pt-6">
          <div className="flex items-center justify-center gap-3 text-sm text-gray-600">
            <Image 
              src="/logo.png" 
              alt="Teklif Up" 
              width={80} 
              height={24}
              className="h-6 w-auto"
            />
            <span className="text-xs">Teklif sistemi tarafından sağlanmaktadır</span>
          </div>
        </div>
      </div>

      {/* Payment Method Selection Modal */}
      <Dialog open={showPaymentModal} onOpenChange={setShowPaymentModal}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Ödeme Yöntemi Seçin</DialogTitle>
          </DialogHeader>
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4 flex items-start gap-2">
            <div className="text-blue-600 mt-0.5">ℹ️</div>
            <p className="text-sm text-blue-800">
              Eklemek istediğiniz alternatif fiyat için ödeme yöntemini seçin.
            </p>
          </div>
          <div className="space-y-2 max-h-96 overflow-y-auto">
            {PAYMENT_METHODS.filter(m => m.value !== 'cash').map((method) => (
              <button
                key={method.value}
                onClick={() => handleSelectPaymentMethod(method.value)}
                className="w-full text-left px-4 py-3 border rounded-lg hover:bg-gray-50 transition-colors"
              >
                {method.label}
              </button>
            ))}
          </div>
        </DialogContent>
      </Dialog>
    </div>
  )
}
