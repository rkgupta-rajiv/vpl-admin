# Player API (used by the Flutter app)

Base URL: `{APP_URL}/api` · Send `Accept: application/json` · Auth: `Authorization: Bearer {token}`

All errors: `{ "success": false, "message": "...", "errors": { "field": ["..."] } }` (422 validation, 401 login again, 403 forbidden, 404, 429 too many attempts).

## Public
| Method | Path | Body | Notes |
|---|---|---|---|
| GET | `/tournament` | – | Active tournament, dates, open/closed, rules, fee |
| POST | `/auth/register` | first_name, surname, age, mobile, email, gender, village, password, password_confirmation | → `token`, `player` |
| POST | `/auth/login` | login (email / mobile / full name), password | → `token`, `player` |
| POST | `/auth/forgot-password` | email | Emails a 6-digit OTP (15 min) |
| POST | `/auth/reset-password` | email, otp, password, password_confirmation | Revokes all tokens |

## Player (Bearer token)
| Method | Path | Body | Notes |
|---|---|---|---|
| GET | `/me` | – | player, documents, tournament, registration, unread_notifications |
| PUT | `/profile` | any of: personal fields, playing_role, batting_style, bowling_style, tshirt_size | Locked after registration is completed |
| POST | `/documents/{photo\|aadhaar_front\|aadhaar_back}` | multipart `file` (jpg/png/webp; photo ≤2 MB, Aadhaar ≤5 MB, ≥200×200) | Re-upload resets verification |
| GET | `/documents/{type}` | – | Own file only |
| POST | `/registration` | jersey_number (0–999), jersey_name | Needs complete profile + 3 docs + open tournament. Fee is locked here |
| PUT | `/registration` | jersey_number, jersey_name | Until completed |
| POST | `/registration/resubmit` | – | After admin rejection |
| GET | `/registration/history` | – | Status timeline |
| GET | `/payment` | – | Fee, QR URL, UPI ID, instructions, status, history |
| POST | `/payment` | multipart: amount (must equal fee), transaction_id, payment_date (≤ today), screenshot | Status → proof_submitted. Duplicate UTR / submission blocked |
| GET | `/payments/{id}/screenshot` | – | Own payment only |
| GET | `/registration/confirmation` | – | Only after payment approval |
| GET | `/notifications` · POST `/notifications/read` | – | In-app notifications |
| POST | `/auth/change-password` · POST `/auth/logout` | – | |

Option keys: gender `male|female|other` · playing_role `batsman|bowler|all_rounder|wicket_keeper` · batting_style `right|left` · bowling_style `right_arm|left_arm|na` · tshirt_size `S|M|L|XL|XXL|XXXL`.
