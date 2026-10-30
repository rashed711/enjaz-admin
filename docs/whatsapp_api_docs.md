# WhatsApp SaaS Gateway — وثيقة توثيق الـ API

> **الإصدار:** 1.1.0 | **آخر تحديث:** 2026-06-05

---

## بنية الـ URL — مهم جداً

### رابط الخادم الأساسي (Base URL)

```txt
https://whatsapp.enjaz.cloud
```

هذا هو **رابط الخادم فقط**، وهو غير كافٍ وحده لإرسال الرسائل.

---

### ⚠️ رابط الإرسال الكامل (Full Endpoint URL)

> **الـ URL الذي تستخدمه في أي نظام خارجي (Make, n8n, Zapier, أو أي API Client) يجب أن يحتوي على الـ Session ID.**

**الصيغة الكاملة:**
```
https://whatsapp.enjaz.cloud/api/sessions/{SESSION_ID}/messages
```

**مثال حقيقي:**
```
https://whatsapp.enjaz.cloud/api/sessions/sess_123456/messages
```

كل مستخدم لديه **Session ID خاص به** يختلف عن الآخرين. يمكنك معرفة الـ Session ID الخاص بك من لوحة التحكم.

---

### الفرق بين الـ Base URL والـ Endpoint URL

| المصطلح | المعنى | مثال |
|---|---|---|
| **Base URL** | رابط الخادم الأساسي فقط | `https://whatsapp.enjaz.cloud` |
| **Endpoint URL** | الرابط الكامل لعملية معينة | `https://whatsapp.enjaz.cloud/api/sessions/sess_123456/messages` |

> ✅ **الخلاصة:** عند الربط من أي نظام خارجي، استخدم دائماً **الـ Endpoint URL الكامل** وليس الـ Base URL وحده.

---

# التحقق من الهوية (Authentication)

يجب تمرير مفتاح الـ API في كل طلب يتم إرساله إلى الخادم.

## طرق المصادقة المدعومة

| الأولوية | الطريقة | اسم الـ Header | مثال على القيمة المرسلة |
|:---:|---|---|---|
| ✅ مُوصى بها | Header مخصص | `x-api-key` | `ak_de6fe9510afd4967e0046ea921e81007a56e259d7e428dad` |
| ✅ مدعومة | Bearer Token | `Authorization` | `Bearer ak_de6fe9510afd4967e0046ea921e81007a56e259d7e428dad` |
| ⚠️ للاختبار فقط | Query Param | — | `?api_key=ak_de6fe9510afd4967e0046ea921e81007a56e259d7e428dad` |

---

### ⚠️ تحذير مهم جداً — Bearer Token

> **لا تكتب كلمة `Bearer` في خانة التوكن في النظام الخارجي.**

معظم الأنظمة والأدوات (مثل Postman, Make, Zapier, n8n) تضيف كلمة `Bearer` **تلقائياً** أمام التوكن قبل إرساله.

لذلك يجب أن تضع في خانة **Token** فقط:
```
ak_de6fe9510afd4967e0046ea921e81007a56e259d7e428dad
```

وليس:
```
Bearer ak_de6fe9510afd4967e0046ea921e81007a56e259d7e428dad   ❌ خاطئ — سيُرفض الطلب بخطأ 403 Forbidden
Bearer Token: ak_de6fe9510afd4967e0046ea921e81007a56e259d7e428dad    ❌ خاطئ — سيُرفض الطلب بخطأ 403 Forbidden
```

**الصيغة الصحيحة** التي يجب أن يصل بها الهيدر للسيرفر هي:
```http
Authorization: Bearer ak_de6fe9510afd4967e0046ea921e81007a56e259d7e428dad
```

---

### ✅ الطريقة الأسهل والأضمن — x-api-key

إذا كان النظام الخارجي يسمح بإضافة Headers مخصصة، استخدم هذا الهيدر تجنباً لأي لبس:

```http
x-api-key: ak_de6fe9510afd4967e0046ea921e81007a56e259d7e428dad
```

لا يحتاج هذا الهيدر لأي بادئة أو صيغة خاصة — فقط مفتاح الـ API مباشرة.

---

## ⚠️ الأخطاء الشائعة في المصادقة

| كود الخطأ | السبب | الحل |
|---|---|---|
| `401 Unauthorized` | لم يتم إرسال مفتاح API أصلاً | تأكد أن الهيدر موجود في الطلب |
| `403 Forbidden` | المفتاح خاطئ أو صيغة Bearer غلط | تحقق من المفتاح وتأكد من عدم تكرار كلمة Bearer |

---

# 1) عرض الأجهزة النشطة (Get Active Sessions)

## Endpoint

```http
GET /api/sessions
```

## Example

```javascript
fetch("https://whatsapp.enjaz.cloud/api/sessions", {
  headers: {
    "x-api-key": "ak_de6fe9510afd4967e0046ea921e81007a56e259d7e428dad"
  }
})
.then(res => res.json())
.then(data => console.log(data));
```

---

# 2) إنشاء جهاز جديد (Create Session)

## Endpoint

```http
POST /api/sessions
```

## Request Body

```json
{
  "name": "جهاز خدمة العملاء"
}
```

## Example

```javascript
fetch("https://whatsapp.enjaz.cloud/api/sessions", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "x-api-key": "ak_de6fe9510afd4967e0046ea921e81007a56e259d7e428dad"
  },
  body: JSON.stringify({
    name: "جهاز خدمة العملاء"
  })
})
.then(res => res.json())
.then(data => console.log(data));
```

---

# 3) جلب QR Code كصورة Base64

## Endpoint

```http
GET /api/sessions/:sessionId/qr
```

## Example

```javascript
fetch("https://whatsapp.enjaz.cloud/api/sessions/sess_123456/qr", {
  headers: {
    "x-api-key": "ak_de6fe9510afd4967e0046ea921e81007a56e259d7e428dad"
  }
})
.then(res => res.json())
.then(data => {
  if (data.status === "QR" && data.qr) {
    document.getElementById("qr-img").src = data.qr;
  } else {
    console.log("حالة الجلسة:", data.status);
  }
});
```

---

# 4) إرسال رسالة أو ملف (Send Message)

يدعم الإرسال الفردي، الجماعي (Bulk)، والمجموعات.

## Endpoint

```http
POST /api/sessions/:sessionId/messages
```

---

## حقول الطلب (Request Body Fields)

> **⚠️ تحذير — أسماء الحقول يجب أن تكون مطابقة تماماً بالأحرف الصغيرة (lowercase).**

### الحقول المتاحة

| الحقل | النوع | إلزامي؟ | الوصف |
|---|---|:---:|---|
| `to` | String أو Array | ✅ **نعم** | رقم المستلم أو قائمة أرقام |
| `text` | String | ⚠️ مطلوب إن لم يكن هناك media | نص الرسالة |
| `media_url` | String | لا | رابط مباشر للصورة/الفيديو/المستند |
| `media_type` | String | لا | نوع الميديا: `image`, `video`, `audio`, `document` |
| `caption` | String | لا | نص مرفق مع الميديا |

---

### ⚠️ أسماء الحقول الخاطئة الشائعة

> **إذا استخدمت أسماء خاطئة، سيرفض السيرفر الطلب بخطأ `400 EMPTY_MESSAGE` أو `400 MISSING_RECIPIENT`.**

| ❌ اسم خاطئ | ✅ الاسم الصحيح |
|---|---|
| `phone` | `to` |
| `number` | `to` |
| `mobile` | `to` |
| `recipient` | `to` |
| `message` | `text` |
| `body` | `text` |
| `content` | `text` |
| `msg` | `text` |

---

### قواعد حقل `to` (رقم المستلم)

يقبل الحقل `to` ثلاثة أشكال:

1. **رقم هاتف عادي (String):** مع كود الدولة بدون `+` أو `00`
   ```json
   "to": "201001234567"
   ```

2. **رقم مصري يبدأ بـ 01:** يتم تحويله تلقائياً بإضافة `20` في البداية
   ```json
   "to": "01001234567"
   ```
   *(سيُرسَل للرقم 201001234567 تلقائياً)*

3. **معرف مجموعة واتساب (JID):** يحتوي على `@g.us`
   ```json
   "to": "1203632948293@g.us"
   ```

4. **إرسال جماعي (Array):** مصفوفة تحتوي أرقاماً أو مجموعات أو كليهما
   ```json
   "to": ["201001234567", "201007654321", "1203632948293@g.us"]
   ```

---

## متطلبات إلزامية قبل الإرسال

> **⚠️ الجلسة يجب أن تكون في حالة CONNECTED حتى يتم الإرسال.**

إذا كانت الجلسة غير متصلة، سيرد السيرفر بـ:
```json
{
  "success": false,
  "error_code": "SESSION_NOT_CONNECTED",
  "message": "Session is not connected"
}
```

---

## مثال — إرسال رسالة نصية (Single)

```javascript
fetch("https://whatsapp.enjaz.cloud/api/sessions/sess_123456/messages", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "x-api-key": "ak_de6fe9510afd4967e0046ea921e81007a56e259d7e428dad"
  },
  body: JSON.stringify({
    to: "201001234567",
    text: "مرحبًا! كود التحقق الخاص بك هو 1234"
  })
})
.then(res => res.json())
.then(data => console.log(data));
```

**الاستجابة الناجحة:**
```json
{
  "success": true,
  "message_id": "msg_1234567890_abc12",
  "status": "queued"
}
```

---

## مثال — إرسال رسالة مع ملف / صورة

```javascript
fetch("https://whatsapp.enjaz.cloud/api/sessions/sess_123456/messages", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "x-api-key": "ak_de6fe9510afd4967e0046ea921e81007a56e259d7e428dad"
  },
  body: JSON.stringify({
    to: "201001234567",
    media_url: "https://example.com/invoice.pdf",
    media_type: "document",
    caption: "مرفق فاتورة الشراء"
  })
})
.then(res => res.json())
.then(data => console.log(data));
```

> **ملاحظة:** إذا لم يتم تحديد `media_type`، يحاول السيرفر تحديد النوع تلقائياً من امتداد الرابط:
> - `.jpg`, `.png`, `.webp` → `image`
> - `.mp4`, `.mov` → `video`
> - `.mp3`, `.ogg`, `.wav` → `audio`
> - غير ذلك → `document`

---

## مثال — إرسال جماعي (Bulk)

```javascript
fetch("https://whatsapp.enjaz.cloud/api/sessions/sess_123456/messages", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "x-api-key": "ak_de6fe9510afd4967e0046ea921e81007a56e259d7e428dad"
  },
  body: JSON.stringify({
    to: ["201001234567", "201007654321", "1203632948293@g.us"],
    text: "تنبيه النظام التلقائي"
  })
})
.then(res => res.json())
.then(data => console.log(data));
```

---

## أكواد الأخطاء لـ Endpoint الإرسال

| كود HTTP | `error_code` | السبب | الحل |
|---|---|---|---|
| `400` | `MISSING_RECIPIENT` | حقل `to` غير موجود | تأكد من إرسال حقل `to` |
| `400` | `EMPTY_MESSAGE` | لا يوجد `text` أو `media_url` أو `media_id` | أرسل على الأقل أحد هذه الحقول |
| `400` | `EMPTY_RECIPIENT_LIST` | المصفوفة `to` فارغة | أضف رقماً واحداً على الأقل |
| `400` | `SESSION_NOT_CONNECTED` | الجلسة غير متصلة | قم بمسح QR وربط الجهاز أولاً |
| `401` | — | لم يتم إرسال مفتاح API | أضف `x-api-key` في الهيدر |
| `403` | — | مفتاح API خاطئ أو صلاحيات غير كافية | تحقق من المفتاح |
| `404` | `SESSION_NOT_FOUND` | الـ `sessionId` غير موجود أو لا يخصك | تحقق من رابط الـ Endpoint |

---

# 5) جلب إعدادات المساعد الذكي (Get AI Config)

## Endpoint

```http
GET /api/sessions/:sessionId/ai-config
```

## Response Example

```json
{
  "aiEnabled": true,
  "aiSystemInstruction": "أنت موظف مبيعات ذكي...",
  "aiProvider": "Gemini",
  "aiApiKey": "AI_API_KEY_HERE",
  "aiModel": "gemini-2.5-flash",
  "aiBaseUrl": "",
  "aiNotifyPhone": "201234567890",
  "aiFiles": []
}
```

---

# 6) تحديث إعدادات المساعد الذكي (Update AI Config)

## Endpoint

```http
PUT /api/sessions/:sessionId/ai-config
```

## Request Body

```json
{
  "aiEnabled": true,
  "aiSystemInstruction": "أنت موظف مبيعات ذكي...",
  "aiProvider": "Gemini",
  "aiApiKey": "AI_API_KEY_HERE",
  "aiModel": "gemini-2.5-flash",
  "aiBaseUrl": "",
  "aiNotifyPhone": "201234567890",
  "storeIntegration": {
    "integrationType": "api",
    "apiUrl": "https://yoursite.com/api/products.php",
    "apiKey": "your_api_token"
  }
}
```

---

# ملخص سريع للربط من أنظمة خارجية

إذا كنت تربط بوابة واتساب هذه من نظام خارجي (مثل Make, n8n, Zapier, أو أي نظام مخصص)، اتبع هذه الخطوات:

## الإعدادات الصحيحة

| الإعداد | القيمة |
|---|---|
| **Method** | `POST` |
| **URL** | `https://whatsapp.enjaz.cloud/api/sessions/YOUR_SESSION_ID/messages` |
| **Header: Content-Type** | `application/json` |
| **Header: x-api-key** | `ak_de6fe9510afd4967e0046ea921e81007a56e259d7e428dad` (بدون أي بادئة) |

## Body الصحيح (JSON)

```json
{
  "to": "201001234567",
  "text": "نص الرسالة هنا"
}
```

## أخطاء يجب تجنبها

- ❌ لا تضع `Bearer` في خانة التوكن إذا كان النظام يضيفه تلقائياً
- ❌ لا تستخدم `phone` أو `number` بدلاً من `to`
- ❌ لا تستخدم `message` أو `body` بدلاً من `text`
- ❌ لا ترسل قبل التأكد أن الجلسة في حالة **CONNECTED**
- ✅ استخدم رقم المستلم مع كود الدولة بدون `+` (مثال: `201001234567`)
