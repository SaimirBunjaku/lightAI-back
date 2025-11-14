# Energy AI - API Documentation

Complete API reference for mobile app integration.

## Base URL
```
http://energy-ai.test/api
```

For production, replace with your actual domain.

---

## Table of Contents
- [Authentication Endpoints](#authentication-endpoints)
- [User Profile Endpoints](#user-profile-endpoints)
- [Device Analysis Endpoints](#device-analysis-endpoints)
- [Saved Devices Endpoints](#saved-devices-endpoints)
- [Energy Insights Endpoints](#energy-insights-endpoints)
- [Public Endpoints](#public-endpoints)
- [Error Responses](#error-responses)

---

## Authentication Endpoints

### 1. Register User

Create a new user account with household information.

**Endpoint:** `POST /api/auth/register`

**Authentication:** Not required

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "property_ownership": "own",
  "house_type": "semi_detached",
  "number_of_occupants": 4,
  "number_of_bedrooms": 3,
  "heating_type": "gas",
  "property_age": "modern"
}
```

**Field Validations:**
- `name`: Required, string, max 255 characters
- `email`: Required, valid email, unique, max 255 characters
- `password`: Required, min 8 characters
- `password_confirmation`: Required, must match password
- `property_ownership`: Required, must be: `own` or `rent`
- `house_type`: Required, must be: `detached`, `semi_detached`, `terraced`, `apartment`, `flat`, `bungalow`, `other`
- `number_of_occupants`: Required, integer, 1-20
- `number_of_bedrooms`: Required, integer, 1-20
- `heating_type`: Required, must be: `gas`, `electric`, `oil`, `solar`, `heat_pump`, `biomass`, `district_heating`, `other`
- `property_age`: Required, must be: `new_build`, `modern`, `established`, `older`, `historic`

**Success Response (201 Created):**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "household": {
        "property_ownership": "own",
        "house_type": "semi_detached",
        "number_of_occupants": 4,
        "number_of_bedrooms": 3,
        "heating_type": "gas",
        "property_age": "modern"
      },
      "created_at": "2025-11-09T09:00:00+00:00"
    },
    "token": "1|abcdefghijklmnopqrstuvwxyz1234567890"
  }
}
```

**Error Response (422 Validation Error):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": [
      "The email has already been taken."
    ],
    "password": [
      "The password must be at least 8 characters."
    ]
  }
}
```

---

### 2. Login User

Authenticate existing user and receive access token.

**Endpoint:** `POST /api/auth/login`

**Authentication:** Not required

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com"
    },
    "token": "2|abcdefghijklmnopqrstuvwxyz1234567890"
  }
}
```

**Error Response (401 Unauthorized):**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

---

### 3. Logout User

Revoke current access token and end session.

**Endpoint:** `POST /api/auth/logout`

**Authentication:** Required (Bearer Token)

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body:** None

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

## User Profile Endpoints

### 4. Get User Profile

Retrieve authenticated user's profile and household information.

**Endpoint:** `GET /api/auth/profile`

**Authentication:** Required (Bearer Token)

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "household": {
        "property_ownership": "own",
        "house_type": "semi_detached",
        "number_of_occupants": 4,
        "number_of_bedrooms": 3,
        "heating_type": "gas",
        "property_age": "modern"
      },
      "created_at": "2025-11-09T09:00:00+00:00"
    }
  }
}
```

---

### 5. Update User Profile

Update user's profile information including household details.

**Endpoint:** `PUT /api/auth/profile`

**Authentication:** Required (Bearer Token)

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body (all fields optional):**
```json
{
  "name": "John Updated",
  "email": "johnupdated@example.com",
  "property_ownership": "rent",
  "house_type": "apartment",
  "number_of_occupants": 2,
  "number_of_bedrooms": 2,
  "heating_type": "electric",
  "property_age": "new_build"
}
```

**To update password, include:**
```json
{
  "current_password": "password123",
  "new_password": "newpassword456",
  "new_password_confirmation": "newpassword456"
}
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Profile updated successfully",
  "data": {
    "user": {
      "id": 1,
      "name": "John Updated",
      "email": "johnupdated@example.com",
      "household": {
        "property_ownership": "rent",
        "house_type": "apartment",
        "number_of_occupants": 2,
        "number_of_bedrooms": 2,
        "heating_type": "electric",
        "property_age": "new_build"
      }
    }
  }
}
```

**Error Response (401 Unauthorized - Wrong Password):**
```json
{
  "success": false,
  "message": "Current password is incorrect"
}
```

---

## Device Analysis Endpoints

### 6. Analyze Device

Upload device image for AI analysis and energy-saving recommendations.

**Endpoint:** `POST /api/device/analyze`

**Authentication:** Required (Bearer Token)

**Headers:**
```
Authorization: Bearer {token}
```

**Request Body (multipart/form-data):**
```
image: [Binary File] (JPEG, PNG, HEIC - Max 5MB)
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Device analyzed successfully",
  "data": {
    "id": 1,
    "device": {
      "category": "refrigerator",
      "brand": "Bosch",
      "model": "Unknown",
      "confidence": "medium"
    },
    "energy": {
      "typical_wattage": "150-250W",
      "idle_wattage": "150W",
      "active_wattage": "250W (when compressor is running)",
      "daily_kwh": "1.2 - 2.0",
      "annual_kwh": "438 - 730",
      "estimated_annual_cost": "$66-110"
    },
    "tips": [
      "Check and adjust the refrigerator and freezer temperature settings. Ideal settings are typically 37°F (3°C) for the refrigerator and 0°F (-18°C) for the freezer.",
      "Regularly clean the condenser coils, typically located at the back of the refrigerator. Dust buildup reduces efficiency and increases energy consumption.",
      "Ensure the door seals are airtight. Test by placing a piece of paper between the door and the frame; if you can easily pull it out with the door closed, the seal needs replacing.",
      "Avoid placing the refrigerator near heat sources such as ovens or direct sunlight, as this forces it to work harder to maintain its temperature.",
      "Minimize the frequency and duration of opening the refrigerator door. Plan what you need before opening and close the door quickly to prevent cold air from escaping."
    ],
    "fallback_level": "category",
    "reasoning": "The image clearly shows a four-door refrigerator with a Bosch logo and a water dispenser. While the exact model isn't identifiable, I'm confident it's a Bosch refrigerator."
  }
}
```

**Error Response (422 Validation Error):**
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "image": [
      "The image field is required.",
      "The image must be a file of type: jpeg, png, jpg, heic.",
      "The image must not be greater than 5120 kilobytes."
    ]
  }
}
```

---

### 7. Get Analysis by ID

Retrieve a specific device analysis.

**Endpoint:** `GET /api/device/analysis/{id}`

**Authentication:** Required (Bearer Token)

**Headers:**
```
Authorization: Bearer {token}
```

**URL Parameters:**
- `id` (integer): Analysis ID

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "device": {
      "category": "laptop",
      "brand": "Apple",
      "model": "MacBook Pro 14-inch (2023)",
      "confidence": "high"
    },
    "energy": {
      "typical_wattage": "30-50W",
      "daily_kwh": "0.24-0.40",
      "annual_kwh": "87.6-146",
      "estimated_annual_cost": "$13-22"
    },
    "tips": [
      "Enable automatic sleep mode after 10 minutes of inactivity",
      "Reduce display brightness to 70-80% for indoor use",
      "Unplug charger when battery is full to reduce phantom load",
      "Use energy saver mode for better battery efficiency",
      "Close unused applications to reduce CPU load and power consumption"
    ],
    "fallback_level": "specific",
    "analyzed_at": "2025-11-09T09:30:00+00:00"
  }
}
```

**Error Response (404 Not Found):**
```json
{
  "success": false,
  "message": "Analysis not found"
}
```

---

### 8. Get User's Analysis History

Retrieve all device analyses for authenticated user.

**Endpoint:** `GET /api/device/history`

**Authentication:** Required (Bearer Token)

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "total": 3,
    "analyses": [
      {
        "id": 3,
        "device": {
          "category": "tv",
          "brand": "Samsung",
          "model": "55-inch QLED",
          "confidence": "high"
        },
        "energy": {
          "typical_wattage": "80-120W",
          "daily_kwh": "0.5-0.8",
          "annual_kwh": "182-292",
          "estimated_annual_cost": "$27-44"
        },
        "fallback_level": "specific",
        "analyzed_at": "2025-11-09T10:00:00+00:00"
      },
      {
        "id": 2,
        "device": {
          "category": "refrigerator",
          "brand": "Bosch",
          "model": "Unknown",
          "confidence": "medium"
        },
        "energy": {
          "typical_wattage": "150-250W",
          "daily_kwh": "1.2-2.0",
          "annual_kwh": "438-730",
          "estimated_annual_cost": "$66-110"
        },
        "fallback_level": "category",
        "analyzed_at": "2025-11-09T09:45:00+00:00"
      },
      {
        "id": 1,
        "device": {
          "category": "laptop",
          "brand": "Apple",
          "model": "MacBook Pro 14-inch",
          "confidence": "high"
        },
        "energy": {
          "typical_wattage": "30-50W",
          "daily_kwh": "0.24-0.40",
          "annual_kwh": "87.6-146",
          "estimated_annual_cost": "$13-22"
        },
        "fallback_level": "specific",
        "analyzed_at": "2025-11-09T09:30:00+00:00"
      }
    ]
  }
}
```

**Empty History Response:**
```json
{
  "success": true,
  "data": {
    "total": 0,
    "analyses": []
  }
}
```

---

## Saved Devices Endpoints

### 9. Save Device to Collection

Save an analyzed device to user's permanent collection for tracking.

**Endpoint:** `POST /api/devices`

**Authentication:** Required (Bearer Token)

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "device_name": "Kitchen Fridge",
  "device_category": "refrigerator",
  "device_brand": "Samsung",
  "device_model": "RF28R7351SR",
  "location": "Kitchen",
  "typical_wattage": "150W",
  "daily_kwh": "3.6 kWh",
  "annual_kwh": "1314 kWh",
  "estimated_annual_cost": "$157.68",
  "energy_saving_tips": [
    "Keep the refrigerator temperature between 37-40°F",
    "Ensure door seals are tight",
    "Keep coils clean"
  ],
  "analysis_id": 1
}
```

**Field Validations:**
- `device_name`: Required, string, max 255 characters
- `device_category`: Required, string, max 100 characters
- `device_brand`: Optional, string, max 100 characters
- `device_model`: Optional, string, max 100 characters
- `location`: Optional, string, max 100 characters
- `typical_wattage`: Optional, string, max 50 characters
- `daily_kwh`: Optional, string, max 50 characters
- `annual_kwh`: Optional, string, max 50 characters
- `estimated_annual_cost`: Optional, string, max 50 characters
- `energy_saving_tips`: Optional, array of strings
- `analysis_id`: Optional, integer (ID of original analysis)

**Success Response (201 Created):**
```json
{
  "success": true,
  "message": "Device saved successfully",
  "data": {
    "device": {
      "id": 1,
      "name": "Kitchen Fridge",
      "category": "refrigerator",
      "brand": "Samsung",
      "model": "RF28R7351SR",
      "location": "Kitchen",
      "energy": {
        "typical_wattage": "150W",
        "daily_kwh": "3.6 kWh",
        "annual_kwh": "1314 kWh",
        "estimated_annual_cost": "$157.68"
      },
      "is_active": true,
      "added_at": "2025-11-09T09:26:32+00:00"
    }
  }
}
```

---

### 10. Get All Saved Devices

Retrieve all saved devices in user's collection.

**Endpoint:** `GET /api/devices`

**Authentication:** Required (Bearer Token)

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "total": 3,
    "devices": [
      {
        "id": 3,
        "name": "Bedroom AC",
        "category": "air_conditioner",
        "brand": "Daikin",
        "model": "FTKM25",
        "location": "Master Bedroom",
        "energy": {
          "typical_wattage": "1200W",
          "daily_kwh": "8.4 kWh",
          "annual_kwh": "840 kWh",
          "estimated_annual_cost": "$100.80"
        },
        "tips": [
          "Set temperature to 24-26°C",
          "Clean filters monthly",
          "Use timer function"
        ],
        "is_active": true,
        "added_at": "2025-11-09T09:27:13+00:00"
      },
      {
        "id": 2,
        "name": "Living Room TV",
        "category": "television",
        "brand": "LG",
        "model": "OLED65C1",
        "location": "Living Room",
        "energy": {
          "typical_wattage": "120W",
          "daily_kwh": "0.48 kWh",
          "annual_kwh": "175 kWh",
          "estimated_annual_cost": "$21.00"
        },
        "tips": [
          "Use energy saving mode",
          "Turn off when not in use",
          "Adjust brightness settings"
        ],
        "is_active": true,
        "added_at": "2025-11-09T09:27:05+00:00"
      },
      {
        "id": 1,
        "name": "Kitchen Fridge",
        "category": "refrigerator",
        "brand": "Samsung",
        "model": "RF28R7351SR",
        "location": "Kitchen",
        "energy": {
          "typical_wattage": "150W",
          "daily_kwh": "3.6 kWh",
          "annual_kwh": "1314 kWh",
          "estimated_annual_cost": "$157.68"
        },
        "tips": [
          "Keep the refrigerator temperature between 37-40°F",
          "Ensure door seals are tight",
          "Keep coils clean"
        ],
        "is_active": true,
        "added_at": "2025-11-09T09:26:32+00:00"
      }
    ]
  }
}
```

**Empty Collection Response:**
```json
{
  "success": true,
  "data": {
    "total": 0,
    "devices": []
  }
}
```

---

### 11. Get Specific Device

Retrieve details of a specific saved device.

**Endpoint:** `GET /api/devices/{id}`

**Authentication:** Required (Bearer Token)

**Headers:**
```
Authorization: Bearer {token}
```

**URL Parameters:**
- `id` (integer): Device ID

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "device": {
      "id": 1,
      "name": "Kitchen Fridge",
      "category": "refrigerator",
      "brand": "Samsung",
      "model": "RF28R7351SR",
      "location": "Kitchen",
      "energy": {
        "typical_wattage": "150W",
        "daily_kwh": "3.6 kWh",
        "annual_kwh": "1314 kWh",
        "estimated_annual_cost": "$157.68"
      },
      "tips": [
        "Keep the refrigerator temperature between 37-40°F",
        "Ensure door seals are tight",
        "Keep coils clean"
      ],
      "is_active": true,
      "added_at": "2025-11-09T09:26:32+00:00"
    }
  }
}
```

**Error Response (404 Not Found):**
```json
{
  "success": false,
  "message": "Device not found"
}
```

---

### 12. Update Device Information

Update device name or location.

**Endpoint:** `PUT /api/devices/{id}`

**Authentication:** Required (Bearer Token)

**Headers:**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**URL Parameters:**
- `id` (integer): Device ID

**Request Body (all fields optional):**
```json
{
  "device_name": "Main TV",
  "location": "Family Room"
}
```

**Field Validations:**
- `device_name`: Optional, string, max 255 characters
- `location`: Optional, string, max 100 characters

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Device updated successfully",
  "data": {
    "device": {
      "id": 2,
      "name": "Main TV",
      "location": "Family Room",
      "is_active": true
    }
  }
}
```

**Error Response (404 Not Found):**
```json
{
  "success": false,
  "message": "Device not found"
}
```

---

### 13. Remove Device from Collection

Soft delete a device (marks as inactive, preserves for historical data).

**Endpoint:** `DELETE /api/devices/{id}`

**Authentication:** Required (Bearer Token)

**Headers:**
```
Authorization: Bearer {token}
```

**URL Parameters:**
- `id` (integer): Device ID

**Success Response (200 OK):**
```json
{
  "success": true,
  "message": "Device removed successfully"
}
```

**Error Response (404 Not Found):**
```json
{
  "success": false,
  "message": "Device not found"
}
```

---

## Energy Insights Endpoints

### 14. Get Energy Insights

Get comprehensive energy consumption statistics and personalized recommendations based on all saved devices.

**Endpoint:** `GET /api/insights`

**Authentication:** Required (Bearer Token)

**Headers:**
```
Authorization: Bearer {token}
```

**Success Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_devices": 3,
      "estimated_daily_consumption": "12.48 kWh",
      "estimated_annual_consumption": "2329 kWh",
      "estimated_annual_cost": "$279.48",
      "average_cost_per_device": "$93.16"
    },
    "breakdown": {
      "by_category": {
        "refrigerator": 1,
        "television": 1,
        "air_conditioner": 1
      },
      "by_location": {
        "Kitchen": 1,
        "Living Room": 1,
        "Master Bedroom": 1
      }
    },
    "highest_consumers": [
      {
        "name": "Kitchen Fridge",
        "category": "refrigerator",
        "annual_kwh": "1314 kWh",
        "estimated_annual_cost": "$157.68"
      },
      {
        "name": "Bedroom AC",
        "category": "air_conditioner",
        "annual_kwh": "840 kWh",
        "estimated_annual_cost": "$100.80"
      },
      {
        "name": "Living Room TV",
        "category": "television",
        "annual_kwh": "175 kWh",
        "estimated_annual_cost": "$21.00"
      }
    ],
    "household_info": {
      "property_type": "detached",
      "occupants": 4,
      "bedrooms": 3,
      "heating_type": "heat_pump",
      "property_age": "modern"
    },
    "recommendations": [
      {
        "type": "general_tip",
        "title": "Smart Power Strips",
        "message": "Use smart power strips to eliminate phantom power draw from electronics.",
        "priority": "low"
      }
    ],
    "potential_savings": {
      "message": "By following the energy-saving tips for your devices, you could save up to 20-30% on energy costs.",
      "estimated_annual_savings": "$69.87"
    }
  }
}
```

**No Devices Response:**
```json
{
  "success": false,
  "message": "No active devices found. Please add devices to your collection first."
}
```

---

## Public Endpoints

### 15. Health Check

Check API status and availability.

**Endpoint:** `GET /api/health`

**Authentication:** Not required

**Success Response (200 OK):**
```json
{
  "status": "ok",
  "timestamp": "2025-11-09T09:00:00+00:00"
}
```

---

### 16. Get Device Categories

Get list of supported device categories for analysis.

**Endpoint:** `GET /api/device/categories`

**Authentication:** Not required

**Success Response (200 OK):**
```json
{
  "success": true,
  "categories": [
    "laptop",
    "desktop",
    "monitor",
    "tv",
    "refrigerator",
    "air_conditioner",
    "microwave",
    "washing_machine",
    "dryer",
    "dishwasher",
    "printer",
    "scanner",
    "router",
    "gaming_console",
    "other"
  ]
}
```

---

## Error Responses

### Authentication Error (401 Unauthorized)
```json
{
  "message": "Unauthenticated."
}
```

### Validation Error (422 Unprocessable Entity)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field_name": [
      "Error message 1",
      "Error message 2"
    ]
  }
}
```

### Server Error (500 Internal Server Error)
```json
{
  "success": false,
  "message": "An error occurred during analysis",
  "error": "Detailed error message (only in debug mode)"
}
```

### Not Found (404)
```json
{
  "success": false,
  "message": "Analysis not found"
}
```

---

## Property & Heating Type Reference

### Property Ownership
- `own` - User owns the property
- `rent` - User rents the property

### House Types
- `detached` - Detached house
- `semi_detached` - Semi-detached house
- `terraced` - Terraced house
- `apartment` - Apartment/flat in building
- `flat` - Flat
- `bungalow` - Bungalow
- `other` - Other type

### Heating Types
- `gas` - Gas central heating
- `electric` - Electric heating
- `oil` - Oil heating
- `solar` - Solar heating
- `heat_pump` - Heat pump
- `biomass` - Biomass heating
- `district_heating` - District/communal heating
- `other` - Other heating type

### Property Age
- `new_build` - 0-5 years old
- `modern` - 6-20 years old
- `established` - 21-50 years old
- `older` - 51-100 years old
- `historic` - 100+ years old

---

## Authentication Flow

### 1. Registration Flow
```
POST /api/auth/register
  ↓
Receive token
  ↓
Store token in app
  ↓
Use token for all authenticated requests
```

### 2. Login Flow
```
POST /api/auth/login
  ↓
Receive token
  ↓
Store token in app
  ↓
Use token for all authenticated requests
```

### 3. Using Authenticated Endpoints
```
Include header:
Authorization: Bearer {your_token}

For all protected endpoints
```

### 4. Logout Flow
```
POST /api/auth/logout
  ↓
Token revoked
  ↓
Clear token from app
```

---
