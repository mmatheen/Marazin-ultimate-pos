# Sale Order POS Integration - Tamil Guide
# POS-ல் Sale Order செயல்படுத்துதல் - தமிழ் வழிகாட்டி

## முக்கிய அம்சங்கள் (Key Features)

### 1. Sale Order Button வசதி
✅ POS பக்கத்தில் புதிய "Sale Order" பொத்தான்
✅ Draft மற்றும் Quotation போன்ற அதே முறையில் வேலை செய்கிறது
✅ Permission அடிப்படையில் மட்டும் காட்டப்படும்

### 2. தானியங்கு Order Number உருவாக்கம்
✅ Format: SO-2025-0001, SO-2025-0002, ...
✅ ஒவ்வொரு location-க்கும் தனித்தனி numbering
✅ ஆண்டு மாறும் போது மீண்டும் 0001 இருந்து தொடங்கும்

### 3. Stock குறைக்கப்படாது
✅ Sale Order உருவாக்கும் போது stock குறையாது
✅ Invoice ஆக மாற்றும் போது மட்டும் stock குறையும்
✅ Batch selection simulate மட்டும் செய்யப்படும்

### 4. Payment தேவையில்லை
✅ Sale Order க்கு payment இல்லை
✅ Invoice ஆக மாற்றும் போது payment செலுத்தலாம்

## பயன்படுத்தும் முறை (How to Use)

### படி 1: Customer தேர்வு செய்தல்
```
⚠️ முக்கியம்: Walk-in Customer க்கு Sale Order செய்ய முடியாது!
```

1. POS பக்கத்தில் Customer dropdown-ஐ திறக்கவும்
2. சரியான customer-ஐ தேர்வு செய்யவும்
3. Walk-in Customer தேர்ந்தால் error message வரும்

**தமிழில் Error Message:**
> "Sale Order-ஐ Walk-In customer-க்கு உருவாக்க முடியாது. தயவுசெய்து சரியான customer-ஐ தேர்வு செய்யவும்."

### படி 2: Products சேர்த்தல்
1. தேவையான products-ஐ தேடவும்
2. Quantity மற்றும் Price நிரப்பவும்
3. Cart-ல் products சேர்க்கவும்

```
⚠️ குறைந்தது ஒரு product கட்டாயம் தேவை!
```

### படி 3: Sale Order Button கிளிக் செய்தல்
1. "Sale Order" பச்சை நிற பொத்தானை கிளிக் செய்யவும்
2. Modal window திறக்கும்

### படி 4: Order விவரங்களை நிரப்புதல்

#### A) Expected Delivery Date (கட்டாயம்)
- **தமிழ்:** எதிர்பார்க்கப்படும் டெலிவரி தேதி
- **கட்டுப்பாடு:** கடந்த கால தேதியை தேர்ந்தெடுக்க முடியாது
- **Error Message:** "Expected delivery date கடந்த காலத்தில் இருக்க முடியாது."

#### B) Order Notes (விருப்பம்)
- **தமிழ்:** ஆர்டர் குறிப்புகள்
- Customer-ன் சிறப்பு கோரிக்கைகளை இங்கே எழுதவும்
- **Example:**
  - "நீல நிறம் கிடைத்தால் தரவும்"
  - "காலை 10 மணிக்கு முன் delivery தேவை"
  - "பேக்கேஜிங் extra கவனமாக செய்யவும்"

### படி 5: Create Sale Order கிளிக் செய்தல்
1. "Create Sale Order" பொத்தானை கிளிக் செய்யவும்
2. அனைத்து validations pass ஆகும்
3. Sale Order உருவாக்கப்படும்

### படி 6: Success Message பார்த்தல்
```
✅ "Sale Order created successfully! Order Number: SO-2025-0001"
```

## விதிமுறைகள் (Validations)

### ❌ இந்த சூழ்நிலைகளில் Sale Order செய்ய முடியாது:

1. **Empty Cart**
   - Error: "Please add at least one product to create a sale order."
   - தமிழ்: "Sale order உருவாக்க குறைந்தது ஒரு product சேர்க்கவும்."

2. **Walk-in Customer**
   - Error: "Sale Orders cannot be created for Walk-In customers."
   - தமிழ்: "Walk-In customer-க்கு sale order செய்ய முடியாது."

3. **No Delivery Date**
   - Error: "Please select an expected delivery date."
   - தமிழ்: "Expected delivery date தேர்வு செய்யவும்."

4. **Past Date Selected**
   - Error: "Expected delivery date cannot be in the past."
   - தமிழ்: "கடந்த கால தேதியை தேர்ந்தெடுக்க முடியாது."

## முக்கிய வித்தியாசங்கள் (Key Differences)

| Feature | Regular Sale (Invoice) | Sale Order |
|---------|----------------------|------------|
| **Stock குறைதல்** | ✅ உடனே குறையும் | ❌ குறையாது |
| **Payment** | ✅ தேவை | ❌ தேவையில்லை |
| **Receipt Print** | ✅ Print ஆகும் | ❌ Print ஆகாது |
| **Invoice Number** | ✅ கிடைக்கும் | ❌ கிடைக்காது |
| **Order Number** | ❌ கிடையாது | ✅ கிடைக்கும் |
| **Customer Type** | ✅ Walk-in OK | ❌ Walk-in தடை |

## Sales Rep பயன்பாடு (For Sales Representatives)

### உங்கள் வேலை:
1. **Customer இடம் போகவும்**
   - Customer என்ன product வேண்டும் என்று கேட்கவும்
   - Quantity கேட்கவும்
   - எப்போது delivery வேண்டும் என்று கேட்கவும்

2. **POS-ல் Order உருவாக்கவும்**
   - Customer-ஐ தேர்வு செய்யவும்
   - Products சேர்க்கவும்
   - Delivery date நிர்ணயிக்கவும்
   - Sale Order பொத்தான் கிளிக் செய்யவும்

3. **Order Number குறித்து வைக்கவும்**
   - Success message-ல் Order Number காட்டும்
   - Example: SO-2025-0001
   - இதை customer-க்கு reference-ஆக கொடுக்கவும்

4. **பின்னர் Shop-ல் அறிவிக்கவும்**
   - Shop staff இந்த order-ஐ prepare செய்வார்கள்
   - Stock இருந்தால் invoice உருவாக்குவார்கள்
   - Stock இல்லாவிட்டால் customer-க்கு inform செய்வார்கள்

## எப்போது பயன்படுத்த வேண்டும்? (When to Use)

### ✅ Sale Order செய்ய வேண்டிய சூழ்நிலைகள்:

1. **பெரிய Order கள்**
   - "எனக்கு 50 boxes தேவை"
   - "100 units அடுத்த வாரம் வேண்டும்"

2. **Customer முன்பதிவு**
   - "இப்போதைக்கு stock குறைவு, அடுத்த வாரம் வருகிறது"
   - "உங்களுக்காக reserve செய்து வைக்கிறேன்"

3. **Special Requirements**
   - "குறிப்பிட்ட color வேண்டும்"
   - "Custom packing தேவை"

### ❌ Sale Order தேவையில்லாத சூழ்நிலைகள்:

1. **உடனடி விற்பனை**
   - Stock உள்ளது
   - Customer இப்போதே வாங்க விரும்புகிறார்
   - பணம் கொடுத்து விடுகிறார்
   - **இதற்கு:** Regular Sale (Invoice) செய்யவும்

2. **Walk-in Customer**
   - பெயர் தெரியாத customer
   - முதல் முறை வருபவர்
   - **இதற்கு:** Walk-in sale செய்யவும்

## பொத்தான் அமைவிடம் (Button Location)

```
POS Screen → Bottom Buttons:

[Job Ticket] [Quotation] [Draft] [Sale Order] [Suspend] [Cancel] [Finalize Sale]
                                    ↑
                              புதிய பொத்தான்!
```

## நிறம் மற்றும் Icon (Color & Icon)

- **Color:** பச்சை (Green) - `btn-outline-success`
- **Icon:** Shopping Cart - `fas fa-shopping-cart`
- **Text:** "Sale Order"

## Permission Settings

### Admin Settings-ல்:
```
Settings → Roles & Permissions → Role → Edit

Permission: "create sale" ✅ (tick செய்யவும்)
```

Sale Order button இந்த permission இருந்தால் மட்டும் தெரியும்.

## Troubleshooting (சிக்கல் தீர்வுகள்)

### 1. பொத்தான் காணவில்லை?
**காரணம்:** Permission இல்லை
**தீர்வு:** Admin-னிடம் "create sale" permission கேட்கவும்

### 2. Walk-in customer error வருகிறது?
**காரணம்:** Walk-in customer தேர்ந்துள்ளீர்கள்
**தீர்வு:** Customer dropdown-ல் சரியான customer தேர்வு செய்யவும்

### 3. Past date error வருகிறது?
**காரணம்:** கடந்த கால தேதி தேர்ந்துள்ளீர்கள்
**தீர்வு:** இன்றைய தேதி அல்லது எதிர்கால தேதி தேர்வு செய்யவும்

### 4. Empty cart error வருகிறது?
**காரணம்:** Products சேர்க்கவில்லை
**தீர்வு:** குறைந்தது ஒரு product cart-ல் சேர்க்கவும்

## Report பார்த்தல் (Viewing Reports)

### Sales Rep-ஆக நீங்கள் உருவாக்கிய orders:
```
Sales → Sale Orders List (விரைவில் வரும்)
→ Filter by: "My Orders"
→ உங்கள் user_id பயன்படுத்தி filter ஆகும்
```

## முடிவுரை (Conclusion)

இந்த Sale Order வசதியால்:
- ✅ Customer orders எளிதாக track செய்யலாம்
- ✅ Sales rep performance measure செய்யலாம்
- ✅ Stock planning சிறப்பாக செய்யலாம்
- ✅ Customer service மேம்படும்

---

**கேள்விகள் இருந்தால்:**
1. System Admin-னிடம் கேளுங்கள்
2. இந்த document-ஐ மீண்டும் படியுங்கள்
3. Training session-க்கு வாருங்கள்

**வாழ்த்துக்கள்! வெற்றிகரமாக Sale Order செய்யலாம்! 🎉**
