# Peşin Ödeme İskontosu - Eksik Adım

## ✅ Tamamlanan
- `applyExtraDiscountToTable` fonksiyonu güncellendi
- `isCashDiscount` parametresi eklendi
- Peşin ödeme ise TÜM ürünlere iskonto uygulanıyor

## ❌ Eksik
Modal'da "Ek İskonto Uygula" butonuna tıklandığında `applyExtraDiscountToTable` fonksiyonunu çağıran kod bulunamadı.

## 🔍 Aranacak
1. Modal HTML kodu nerede?
2. "Ek İskonto Uygula" butonunun event handler'ı nerede?
3. `applyExtraDiscountToTable(products, discountRate, true)` çağrısı nerede yapılmalı?

## 📝 Yapılması Gereken
Peşin ödeme kampanyası için butona tıklandığında:
```javascript
applyExtraDiscountToTable(products, 10, true); // true = isCashDiscount
```

Ana Bayi ek iskonto için:
```javascript
applyExtraDiscountToTable(products, 5, false); // false = normal extra discount
```
