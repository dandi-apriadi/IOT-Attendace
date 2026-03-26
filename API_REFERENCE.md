# 🔌 API REFERENCE DOCUMENTATION
## IoT Attendance System API Endpoints

**Base URL:** `http://localhost:8000/api`  
**Authentication:** Device Token (X-Device-Token header)

---

## 📡 ATTENDANCE ENDPOINTS

### POST /api/absensi
Record attendance from IoT device

**Request Headers:**
```
X-Device-Token: change-this-token-for-iot-devices
X-Device-Id: device-id (optional but recommended)
Content-Type: application/json
```

**Request Body:**
```json
{
  "identifier": "RFID_UID_OR_BIOMETRIC_DATA",
  "type": "RFID|Fingerprint|Face Recognition|Barcode"
}
```

**Successful Response (200 OK):**
```json
{
  "status": "success",
  "data": {
    "nama": "Budi Santoso",
    "mata_kuliah": "Pemrograman Web",
    "waktu": "10:30:45",
    "keterangan": "Hadir|Telat"
  }
}
```

**Error Response (400 Bad Request):**
```json
{
  "message": "Tidak ada jadwal aktif saat ini"
}
```

**Error Response (404 Not Found):**
```json
{
  "message": "Mahasiswa tidak terdaftar"
}
```

**Error Response (401 Unauthorized):**
```json
{
  "message": "Unauthorized device token."
}
```

**Error Response (422 Unprocessable Entity):**
```json
{
  "message": "The type field must be one of: RFID, Fingerprint, Face Recognition, Barcode."
}
```

---

## 🔑 ATTENDANCE TYPES SUPPORTED

| Type | Method | Data Format | Example |
|------|--------|-------------|---------|
| RFID | Card Reader | UID String | `E2808A12` |
| Fingerprint | Biometric Scanner | Hash/Template | `FINGER_TEMPLATE_001` |
| Face Recognition | Camera | Face Model Data | `FACE_MODEL_DATA_001` |
| Barcode | Barcode Scanner | Code String | `20220001` |

---

## 🔐 AUTHENTICATION

### Option 1: Device-Specific Token
```
Headers:
  X-Device-Token: device-token
  X-Device-Id: device-id
```
*Recommended for production*

### Option 2: Global Token
```
Headers:
  X-Device-Token: change-this-token-for-iot-devices
```
*Default fallback option*

### Get Device Token Hash
```sql
SELECT device_id, token_hash FROM devices WHERE is_active = 1;
```

---

## 📊 RESPONSE STATUS CODES

| Code | Status | Meaning |
|------|--------|---------|
| 200 | OK | Attendance recorded successfully |
| 400 | Bad Request | No active schedule at this time |
| 401 | Unauthorized | Invalid device token |
| 404 | Not Found | Student not registered |
| 422 | Unprocessable Entity | Validation error |

---

## 🧪 TESTING API WITH CURL

### Test 1: Valid RFID Request
```bash
curl -X POST http://localhost:8000/api/absensi \
  -H "X-Device-Token: change-this-token-for-iot-devices" \
  -H "X-Device-Id: test-device-001" \
  -H "Content-Type: application/json" \
  -d '{"identifier": "00ACFC25", "type": "RFID"}'
```

### Test 2: Invalid Token
```bash
curl -X POST http://localhost:8000/api/absensi \
  -H "X-Device-Token: wrong-token" \
  -H "Content-Type: application/json" \
  -d '{"identifier": "00ACFC25", "type": "RFID"}'
```

### Test 3: Without Token
```bash
curl -X POST http://localhost:8000/api/absensi \
  -H "Content-Type: application/json" \
  -d '{"identifier": "00ACFC25", "type": "RFID"}'
```

### Test 4: Invalid Student
```bash
curl -X POST http://localhost:8000/api/absensi \
  -H "X-Device-Token: change-this-token-for-iot-devices" \
  -H "Content-Type: application/json" \
  -d '{"identifier": "INVALID_ID", "type": "RFID"}'
```

---

## 🔄 ATTENDANCE FLOW

```
1. IoT Device scans student ID (RFID/Barcode/etc)
   ↓
2. Device sends request to POST /api/absensi
   - Include authentication headers
   - Include identifier and type
   ↓
3. API validates device token
   ↓
4. API finds student by identifier
   ↓
5. API finds active schedule for student's class
   ↓
6. API calculates attendance status (Hadir/Telat)
   ↓
7. API saves to database
   ↓
8. API returns response with student info & status
   ↓
9. IoT Device displays result to student
```

---

## 🗄️ DATABASE SCHEMA

### Students (mahasiswa)
```
- id (primary key)
- nim (unique identifier)
- nama (student name)
- kelas_id (foreign key)
- rfid_uid
- fingerprint_data
- face_model_data
- barcode_id
- created_at
- updated_at
```

### Attendance (absensi)
```
- id (primary key)
- mahasiswa_id (foreign key)
- jadwal_id (foreign key)
- tanggal (date)
- waktu_tap (time)
- metode_absensi (RFID/Fingerprint/Face Recognition/Barcode)
- status (Hadir/Telat/Sakit/Izin/Alpa)
- created_at
- updated_at
```

### Schedules (jadwal)
```
- id (primary key)
- kelas_id (foreign key)
- mata_kuliah_id (foreign key)
- user_id (dosen - foreign key)
- hari (day name)
- jam_mulai (start time)
- jam_selesai (end time)
- created_at
- updated_at
```

### Devices (devices)
```
- id (primary key)
- device_id (unique)
- name
- token_hash
- is_active (boolean)
- last_seen_at (timestamp)
- created_at
- updated_at
```

---

## 🛡️ SECURITY CONSIDERATIONS

1. **Always use HTTPS in production** (not HTTP)
2. **Rotate device tokens regularly**
3. **Use unique tokens per device** (not global token)
4. **Monitor API logs for suspicious activities**
5. **Limit request rate per device**
6. **Validate identifier format on device side**
7. **Implement timeout for old records**

---

## 📝 LOGFILE LOCATION

API logs can be found at:
```
storage/logs/laravel.log
```

Monitor for:
- Invalid token attempts
- Device not found errors
- Database errors
- Validation failures

---

## 🔧 TROUBLESHOOTING

### Problem: 401 Unauthorized
**Solution:** 
- Check device token in .env file
- Verify device is registered in database
- Check headers format (case-sensitive: `X-Device-Token`)

### Problem: 404 Student Not Found
**Solution:**
- Register student in database first
- Match identifier exactly (case-sensitive)
- Check identifier is stored in correct column (rfid_uid, etc)

### Problem: 400 No Active Schedule
**Solution:**
- Create schedule for current day & time
- Check schedule hari (day name) matches today
- Check jam_mulai and jam_selesai covers current time

### Problem: 422 Validation Error
**Solution:**
- Check type field is one of: RFID, Fingerprint, Face Recognition, Barcode
- Check identifier field is not empty
- Check request body is valid JSON

---

## 📊 EXAMPLE SUCCESSFUL FLOW

**Step 1: Setup**
```bash
# Register device
POST /api/setup/register-device
Body: {"device_id": "ROOM_101", "token": "my-secure-token"}
```

**Step 2: Record Attendance**
```bash
curl -X POST http://localhost:8000/api/absensi \
  -H "X-Device-Token: change-this-token-for-iot-devices" \
  -H "X-Device-Id: ROOM_101" \
  -H "Content-Type: application/json" \
  -d '{"identifier": "00ACFC25", "type": "RFID"}'
```

**Step 3: Response**
```json
{
  "status": "success",
  "data": {
    "nama": "Dewi Santoso",
    "mata_kuliah": "Cloud Computing",
    "waktu": "08:30:15",
    "keterangan": "Hadir"
  }
}
```

---

## 📞 SUPPORT

For issues or questions:
1. Check logs: `storage/logs/laravel.log`
2. Run tests: `php artisan test:api`
3. Check database: `php artisan tinker`

---

**API Version:** v1.0  
**Last Updated:** 25 Maret 2026  
**Status:** ✅ PRODUCTION READY
