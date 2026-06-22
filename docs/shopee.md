# Shopee Open Platform v2 — Integration Reference

Working reference for the order → ship → tracking → waybill flow. Covers when to call each API, required conditions/statuses, detailed request/response tables, and gotchas.

> **Scope:** Local sellers, shop-level APIs only. Shop APIs sign with the 5-part signature (`partner_id + path + timestamp + access_token + shop_id`). Auth endpoints use the 3-part public signature.

---

## Table of Contents
1. [Core Concepts](#core-concepts)
2. [Order Status Lifecycle](#order-status-lifecycle)
3. [Package Fulfillment Status](#package-fulfillment-status)
4. [The Full Flow](#the-full-flow)
5. [Order APIs](#order-apis)
6. [Split Order APIs](#split-order-apis)
7. [Logistics — Shipment APIs](#logistics--shipment-apis)
8. [Logistics — Waybill APIs](#logistics--waybill-apis)
9. [Webhooks / Push](#webhooks--push)
10. [Critical Rules & Gotchas](#critical-rules--gotchas)
11. [Unverified — Test in Sandbox](#unverified--test-in-sandbox)

---

## Core Concepts

### Entity model
- **Order** — created after checkout. One order can contain multiple items. Identified by `order_sn`.
- **Package** — the unit of shipment. One order can split into multiple packages. Identified by `package_number`.
- **Item** — individual products within an order/package.

### Linking keys
| Key | Identifies | When needed |
|-----|-----------|-------------|
| `order_sn` | the order | **Always** — primary key through the whole flow |
| `package_number` | a package within an order | **Split orders** + package-level calls |

Non-split: use `order_sn`, **omit** `package_number` (never send `""`). Split: supply `package_number` per package.

### AWB rule
**One AWB = one parcel/package.** Never shared across parcels. Split order → one AWB per package.

---

## Order Status Lifecycle

```
PENDING -> UNPAID -> READY_TO_SHIP -> PROCESSED -> SHIPPED -> TO_CONFIRM_RECEIVE -> COMPLETED
```
Branches: `RETRY_SHIP`, `IN_CANCEL -> CANCELLED`, `TO_RETURN`

| Status | Meaning | Internal map |
|--------|---------|--------------|
| `PENDING` | Order held for Shopee verification (paid but being checked). Gated behind `request_order_status_pending=true`. | UNPROCESSED |
| `UNPAID` | Order created, buyer hasn't paid yet | UNPROCESSED |
| `READY_TO_SHIP` | Payment done — seller can arrange shipment. **"Process" acts here** | UNPROCESSED |
| `PROCESSED` | Shipment arranged via ship_order. **Fires BEFORE tracking number exists** | PROCESSED |
| `SHIPPED` | Parcel dropped to / picked up by 3PL | PROCESSED |
| `TO_CONFIRM_RECEIVE` | Buyer received the order | PROCESSED |
| `COMPLETED` | Order completed | PROCESSED |
| `RETRY_SHIP` | 3PL pickup failed — re-arrange | PROCESSED |
| `IN_CANCEL` | Cancellation in progress | CANCELLED |
| `CANCELLED` | Order cancelled | CANCELLED |
| `TO_RETURN` | Buyer requested return | RETURNING |

**pending_terms:** `SYSTEM_PENDING` (internal processing), `KYC_PENDING` (TW CB only), `ARRANGE_SHIPMENT_PENDING` (3PL capacity; label within 4 days of payment).

---

## Package Fulfillment Status

| Status | Meaning |
|--------|---------|
| `LOGISTICS_NOT_START` | Initial; not ready for fulfillment |
| `LOGISTICS_READY` | Ready (non-COD: paid; COD: screened). **Call get_shipping_parameter here** |
| `LOGISTICS_REQUEST_CREATED` | Shipment arranged |
| `LOGISTICS_PICKUP_DONE` | Handed to 3PL. **Cannot create waybill after this** |
| `LOGISTICS_DELIVERY_DONE` | Delivered |
| `LOGISTICS_PICKUP_RETRY` | Pending 3PL retry. **Use update_shipping_order here** |
| `LOGISTICS_INVALID` | Cancelled at LOGISTICS_READY |
| `LOGISTICS_REQUEST_CANCELED` | Cancelled at LOGISTICS_REQUEST_CREATED |
| `LOGISTICS_PICKUP_FAILED` | 3PL pickup failed |
| `LOGISTICS_DELIVERY_FAILED` | 3PL delivery failed |
| `LOGISTICS_LOST` | 3PL lost the package |

**Package status filter (search_package_list):** 0=All, 1=Pending (NOT_START), **2=ToProcess (READY or PICKUP_RETRY)**, 3=Processed (REQUEST_CREATED).

---

## The Full Flow

```
A. INTAKE
   order_status_push (code 3) -> get_order_detail -> store order

B. PROCESS (arrange shipment)
   1. get_shipping_parameter(order_sn[, package_number])  -> info_needed
   2. [pick ONE method + params]
   3. ship_order(order_sn, method)                        -> order PROCESSED (NO tracking yet)

C. TRACKING (async — from 3PL)
   4. get_tracking_number(order_sn)  -> POLL until non-empty (5-min interval)
      OR listen order_trackingno_push (code 4)

D. WAYBILL (per package; only after tracking exists)
   5. get_shipping_document_parameter  -> suggested + selectable type
   6. create_shipping_document (needs tracking_number)
   7. get_shipping_document_result     -> POLL until READY (or code 15 push)
   8. download_shipping_document       -> PDF/HTML/ZIP

E. MIGRATE -> push order + waybill to the other management system
```

**Trigger rule:** Waybill chain starts on **tracking-ready** (code 4 / get_tracking_number returns), NOT on PROCESSED.

---

# Order APIs

## get_order_detail
`GET /api/v2/order/get_order_detail`
**Use:** Get full order data after a push or during sync (push only carries ordersn + status).

### Request Parameters
| Name | Type | Required | Sample | Description |
|------|------|----------|--------|-------------|
| order_sn_list | string | True | 201214JAJXU6G7,201214JASXYXY6 | Set of order_sn, comma-joined. Limit [1,50] |
| request_order_status_pending | boolean | False | true | True = support PENDING status + return pending_terms; False/omit = old logic |
| response_optional_fields | string | False | total_amount | Response fields wanted, comma-joined (see values below) |

**response_optional_fields values:** buyer_user_id, buyer_username, estimated_shipping_fee, recipient_address, actual_shipping_fee, goods_to_declare, note, note_update_time, item_list, pay_time, dropshipper, dropshipper_phone, split_up, buyer_cancel_reason, cancel_by, cancel_reason, actual_shipping_fee_confirmed, buyer_cpf_id, fulfillment_flag, pickup_done_time, package_list, shipping_carrier, payment_method, total_amount, invoice_data, order_chargeable_weight_gram, return_request_due_date, edt, payment_info, international_label

### Response Parameters
| Name | Type | Sample | Description |
|------|------|--------|-------------|
| error | string | | Error type if any. Empty if no error |
| message | string | | Error details if any |
| request_id | string | | Identifier for error tracking |
| response | object | | |
| &nbsp;&nbsp;order_list | object[] | | List of orders |
| &nbsp;&nbsp;&nbsp;&nbsp;order_sn | string | 220909... | Order id |
| &nbsp;&nbsp;&nbsp;&nbsp;region | string | SG | Region |
| &nbsp;&nbsp;&nbsp;&nbsp;currency | string | SGD | Currency |
| &nbsp;&nbsp;&nbsp;&nbsp;cod | boolean | false | Whether COD |
| &nbsp;&nbsp;&nbsp;&nbsp;order_status | string | READY_TO_SHIP | Current order status |
| &nbsp;&nbsp;&nbsp;&nbsp;create_time | int | 1660123127 | Creation timestamp |
| &nbsp;&nbsp;&nbsp;&nbsp;update_time | int | 1660123127 | Last update timestamp |
| &nbsp;&nbsp;&nbsp;&nbsp;days_to_ship | int | 3 | Shipping prep days |
| &nbsp;&nbsp;&nbsp;&nbsp;ship_by_date | int | 1662209873 | Ship-by deadline |
| &nbsp;&nbsp;&nbsp;&nbsp;buyer_user_id | int | 12345 | (optional) Buyer id |
| &nbsp;&nbsp;&nbsp;&nbsp;buyer_username | string | john | (optional) Buyer username |
| &nbsp;&nbsp;&nbsp;&nbsp;recipient_address | object | | (optional) Recipient address |
| &nbsp;&nbsp;&nbsp;&nbsp;total_amount | float | 100.0 | (optional) Order total |
| &nbsp;&nbsp;&nbsp;&nbsp;payment_method | string | Credit Card | (optional) Payment method |
| &nbsp;&nbsp;&nbsp;&nbsp;shipping_carrier | string | Standard | (optional) Logistics provider |
| &nbsp;&nbsp;&nbsp;&nbsp;split_up | boolean | false | (optional) Whether split |
| &nbsp;&nbsp;&nbsp;&nbsp;item_list | object[] | | (optional) Items |
| &nbsp;&nbsp;&nbsp;&nbsp;package_list | object[] | | (optional) Packages |
| &nbsp;&nbsp;&nbsp;&nbsp;pay_time | int | 1660123127 | (optional) Payment time |
| &nbsp;&nbsp;&nbsp;&nbsp;pending_terms | string[] | ["SYSTEM_PENDING"] | (conditional) Why pending |

> **Note:** Missing fields -> you didn't request them in `response_optional_fields`.

---

## get_order_list
`GET /api/v2/order/get_order_list`
**Use:** Reconciliation sync — fetch orders by time window to catch anything webhooks missed.

### Request Parameters
| Name | Type | Required | Sample | Description |
|------|------|----------|--------|-------------|
| time_range_field | string | True | update_time | Use `update_time` (catches status changes, not just new orders) |
| time_from | int | True | 1607235072 | Window start (timestamp) |
| time_to | int | True | 1607407872 | Window end. Max window per API cap (often 15 days) — chunk if larger |
| page_size | int | True | 50 | 1–100 |
| cursor | string | False | "" | Pagination |
| order_status | string | False | READY_TO_SHIP | Optional filter |
| response_optional_fields | string | False | order_status | e.g. order_status |

### Response Parameters
| Name | Type | Sample | Description |
|------|------|--------|-------------|
| error | string | | |
| message | string | | |
| response | object | | |
| &nbsp;&nbsp;order_list | object[] | | |
| &nbsp;&nbsp;&nbsp;&nbsp;order_sn | string | 201214... | Order id |
| &nbsp;&nbsp;&nbsp;&nbsp;order_status | string | READY_TO_SHIP | (if requested) |
| &nbsp;&nbsp;more | boolean | true | More pages exist |
| &nbsp;&nbsp;next_cursor | string | | Pass as cursor next call |

> **Note:** Watermark = `last_synced_at` per shop. Overlap window slightly (-5 min). Advance watermark ONLY on success.

---

## get_shipment_list
`GET /api/v2/order/get_shipment_list`
**Use:** Get orders at READY_TO_SHIP or RETRY_SHIP to start the shipping flow.

### Request Parameters
| Name | Type | Required | Sample | Description |
|------|------|----------|--------|-------------|
| cursor | string | False | "" | Pagination; default "" |
| page_size | int32 | True | 20 | 1–100 |

### Response Parameters
| Name | Type | Sample | Description |
|------|------|--------|-------------|
| error | string | common.error_auth | |
| message | string | | |
| response | object | | |
| &nbsp;&nbsp;order_list | object[] | | List of shipment orders |
| &nbsp;&nbsp;&nbsp;&nbsp;order_sn | string | 2003160SXK2A3T | Order id |
| &nbsp;&nbsp;&nbsp;&nbsp;package_number | string | 38027870177402 | Package id |
| &nbsp;&nbsp;more | boolean | true | More pages |
| &nbsp;&nbsp;next_cursor | string | 20 | Pass as cursor; empty when more=false |
| request_id | string | | |

---

## search_package_list
`POST /api/v2/order/search_package_list`
**Use:** Find packages not yet shipped (preferred), with filters.

### Request Parameters (payload)
| Name | Type | Required | Sample | Description |
|------|------|----------|--------|-------------|
| filter | object | True | | Filter object |
| &nbsp;&nbsp;package_status | int | | 2 | 2 = ToProcess (to ship) |
| &nbsp;&nbsp;product_location_ids | string[] | | ["VN0005EIZ"] | Warehouse ids |
| &nbsp;&nbsp;logistics_channel_ids | int[] | | [50021] | Logistics channel ids |
| &nbsp;&nbsp;fulfillment_type | int | | 2 | |
| &nbsp;&nbsp;invoice_pending | boolean | | false | |
| &nbsp;&nbsp;sorting_group | int | | 1 | |
| &nbsp;&nbsp;order_type | int | | 0 | |
| &nbsp;&nbsp;is_pre_order | int | | 0 | |
| &nbsp;&nbsp;shipping_priority | int | | 0 | |
| pagination | object | True | | |
| &nbsp;&nbsp;page_size | int | | 5 | |
| &nbsp;&nbsp;cursor | string | | "" | |
| sort | object | False | | |
| &nbsp;&nbsp;sort_type | int | | 1 | |
| &nbsp;&nbsp;ascending | boolean | | false | |

### Response Parameters
| Name | Type | Sample | Description |
|------|------|--------|-------------|
| error | string | | |
| message | string | | |
| response | object | | |
| &nbsp;&nbsp;packages_list | object[] | | |
| &nbsp;&nbsp;&nbsp;&nbsp;order_sn | string | 250211UJM7EVM7 | Order id |
| &nbsp;&nbsp;&nbsp;&nbsp;package_number | string | OFG192947720204989 | Package id |
| &nbsp;&nbsp;&nbsp;&nbsp;logistics_channel_id | int32 | 50021 | Logistics channel |
| &nbsp;&nbsp;&nbsp;&nbsp;product_location_id | string | VN0005EIZ | Warehouse — pass to mass step |
| &nbsp;&nbsp;&nbsp;&nbsp;sorting_group | string | North | [TW 30029 only] after arrangement |
| &nbsp;&nbsp;&nbsp;&nbsp;is_shipment_arranged | boolean | false | true = already arranged (no dup); false = needs arrangement |
| &nbsp;&nbsp;pagination | object | | |
| &nbsp;&nbsp;&nbsp;&nbsp;total_count | int64 | 320 | Total matching |
| &nbsp;&nbsp;&nbsp;&nbsp;next_cursor | string | | Pass next call |
| &nbsp;&nbsp;&nbsp;&nbsp;more | boolean | true | More pages |
| request_id | string | | |

> **Note:** `is_shipment_arranged` (only when LOGISTICS_READY) is your **idempotency guard** — true means processing already started, don't double-ship.

---

## get_package_detail
`GET /api/v2/order/get_package_detail`
**Use:** Get package details.

### Request Parameters
| Name | Type | Required | Sample | Description |
|------|------|----------|--------|-------------|
| package_number_list | string | True | OFG1156498731071468,OFG199593509207187 | Set of package_number, comma-joined. Limit [1,50] |

### Response Parameters
| Name | Type | Sample | Description |
|------|------|--------|-------------|
| error | string | | |
| message | string | | |
| request_id | string | | |
| response | object | | |
| &nbsp;&nbsp;package_list | object[] | | |
| &nbsp;&nbsp;&nbsp;&nbsp;order_sn | string | 220831EGF1JMXF | Order id |
| &nbsp;&nbsp;&nbsp;&nbsp;package_number | string | OFG1156498731071468 | Package id |
| &nbsp;&nbsp;&nbsp;&nbsp;fulfillment_status | string | LOGISTICS_READY | Package fulfillment status |
| &nbsp;&nbsp;&nbsp;&nbsp;update_time | int64 | 1661950674 | Last change |
| &nbsp;&nbsp;&nbsp;&nbsp;logistics_channel_id | int64 | 80008 | Logistics channel |
| &nbsp;&nbsp;&nbsp;&nbsp;shipping_carrier | string | JNE Trucking | Carrier buyer selected |
| &nbsp;&nbsp;&nbsp;&nbsp;allow_self_design_awb | boolean | true | false = only system AWB |
| &nbsp;&nbsp;&nbsp;&nbsp;days_to_ship | int64 | 3 | Prep days |
| &nbsp;&nbsp;&nbsp;&nbsp;ship_by_date | int64 | 1662209873 | Ship deadline |
| &nbsp;&nbsp;&nbsp;&nbsp;pending_terms | string[] | ["SYSTEM_PENDING"] | Pending reasons |
| &nbsp;&nbsp;&nbsp;&nbsp;pending_description | string[] | ["..."] | Pending descriptions |
| &nbsp;&nbsp;&nbsp;&nbsp;tracking_number | string | | Tracking number |
| &nbsp;&nbsp;&nbsp;&nbsp;pickup_done_time | int64 | | Pickup done timestamp |
| &nbsp;&nbsp;&nbsp;&nbsp;is_split_up | boolean | false | Whether parcel split |
| &nbsp;&nbsp;&nbsp;&nbsp;can_split_order | boolean | false | Can call split_order |
| &nbsp;&nbsp;&nbsp;&nbsp;can_unsplit_order | boolean | false | Can call unsplit_order |
| &nbsp;&nbsp;&nbsp;&nbsp;is_shipment_arranged | boolean | false | true = already arranged |
| &nbsp;&nbsp;&nbsp;&nbsp;is_pre_order | boolean | false | Pre-order |
| &nbsp;&nbsp;&nbsp;&nbsp;item_list | object[] | | Items in package |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;item_id | int64 | 2200149592 | Item id |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;model_id | int64 | | Model id |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;item_sku | string | | Item SKU |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;model_quantity | int64 | 1 | Quantity |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;order_item_id | int64 | 2200149592 | Order item id (same for bundle) |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;promotion_group_id | int64 | | Promotion id |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;product_location_id | string | | Warehouse id |
| &nbsp;&nbsp;&nbsp;&nbsp;recipient_address | object | | Recipient address (masked per market) |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;name | string | b***r | Recipient name |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;phone | string | ******78 | Phone |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;full_address | string | ******11 | Full address |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;region/state/city/district/town/zipcode | string | | Address parts |
| &nbsp;&nbsp;&nbsp;&nbsp;status_info_tag | object | | Urgency tag (tag_id, timestamp) |
| &nbsp;&nbsp;&nbsp;&nbsp;preparation_end_time | timestamp | 1772276400 | Packing/print deadline (auto-call channels) |
| &nbsp;&nbsp;&nbsp;&nbsp;driver_info | object | | Driver info after successful call |
| &nbsp;&nbsp;&nbsp;&nbsp;can_full_cancel_order | boolean | true | Full cancel allowed |
| &nbsp;&nbsp;&nbsp;&nbsp;can_partial_cancel_order | boolean | false | Partial cancel allowed |

---

# Split Order APIs

## split_order
`POST /api/v2/order/split_order`
**Use:** Split one order into multiple packages.
**Conditions:** order_status = **READY_TO_SHIP**; include ALL items in one request; max parcels 30 (TW) / 5 (other); same item+model can't be split (unless whitelisted); installation-service orders can't split by quantity; needs split permission.

### Request Parameters
| Name | Type | Required | Sample | Description |
|------|------|----------|--------|-------------|
| order_sn | string | True | 2012300NQJVTYN | Order id |
| package_list | object[] | True | | Packages to split into |
| &nbsp;&nbsp;item_list | object[] | True | | Items under this package |
| &nbsp;&nbsp;&nbsp;&nbsp;item_id | int64 | True | 3600140554 | Item id |
| &nbsp;&nbsp;&nbsp;&nbsp;model_id | int64 | True | 10000605797 | Model id (0 if no variation) |
| &nbsp;&nbsp;&nbsp;&nbsp;order_item_id | int | False | | Order item id (same for bundle) |
| &nbsp;&nbsp;&nbsp;&nbsp;promotion_group_id | int | False | | Required for add-on/bundle deal items |
| &nbsp;&nbsp;&nbsp;&nbsp;model_quantity | int32 | False | 2 | Qty per package (unit-level split whitelist only) |

### Response Parameters
| Name | Type | Sample | Description |
|------|------|--------|-------------|
| error | string | | |
| message | string | | |
| response | object | | |
| &nbsp;&nbsp;order_sn | string | 2012300NQJVTYN | Order id |
| &nbsp;&nbsp;package_list | object[] | | Packages created |
| &nbsp;&nbsp;&nbsp;&nbsp;package_number | string | 2521728636547073446 | Generated package id |
| &nbsp;&nbsp;&nbsp;&nbsp;item_list | object[] | | Items in this package |
| request_id | string | | |

---

## unsplit_order
`POST /api/v2/order/unsplit_order`
**Use:** Undo a split (order back to one package).
**Conditions:** order_status = **READY_TO_SHIP** (no parcel shipped).

### Request Parameters
| Name | Type | Required | Sample | Description |
|------|------|----------|--------|-------------|
| order_sn | string | True | 2012312AVA7HVN | Order id |

### Response Parameters
| Name | Type | Sample | Description |
|------|------|--------|-------------|
| error | string | | |
| message | string | | |
| request_id | string | | |

---

# Logistics — Shipment APIs

## get_shipping_parameter
`GET /api/v2/logistics/get_shipping_parameter`
**Use:** Check if package supports pickup / dropoff / non-integrated and what each needs.
**Conditions (fulfillment status):** 1) LOGISTICS_READY; or 2) LOGISTICS_PICKUP_RETRY; or 3) LOGISTICS_REQUEST_CREATED + Instant Order Reschedule.

### Request Parameters
| Name | Type | Required | Sample | Description |
|------|------|----------|--------|-------------|
| order_sn | string | True | 201214JASXYXY6 | Order id |
| package_number | string | False | OFG134731496217591 | Package id (split). Omit (not "") for non-split |

### Response Parameters
| Name | Type | Sample | Description |
|------|------|--------|-------------|
| request_id | string | | |
| error | string | | |
| message | string | | |
| response | object | | |
| &nbsp;&nbsp;info_needed | object | | Required params per method. Use ONE method; always include chosen method key even if empty |
| &nbsp;&nbsp;&nbsp;&nbsp;dropoff | string[] | [] | May contain branch_id, sender_real_name, tracking_no, slug |
| &nbsp;&nbsp;&nbsp;&nbsp;pickup | string[] | ["address_id","pickup_time_id"] | Choose one address_id + its pickup_time_id |
| &nbsp;&nbsp;&nbsp;&nbsp;non_integrated | string[] | | May contain tracking_no |
| &nbsp;&nbsp;dropoff | object | | Dropoff info |
| &nbsp;&nbsp;&nbsp;&nbsp;branch_list | object[] | | branch_id, region, state, city, address, zipcode, district, town |
| &nbsp;&nbsp;&nbsp;&nbsp;slug_list | object[] | | TW 3PL drop-off partners (slug, slug_name) |
| &nbsp;&nbsp;pickup | object | | Pickup info |
| &nbsp;&nbsp;&nbsp;&nbsp;address_list | object[] | | Available pickup addresses |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;address_id | int64 | 234 | Address id |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;region/state/city/district/town/address/zipcode | string | | Address parts |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;address_flag | string[] | ["default_address"] | default_address, pickup_address, return_address, current_address |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;time_slot_list | object[] | | Pickup times for this address |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;date | timestamp | 1608103685 | Pickup date |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;time_text | string | | Text description (some channels) |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;pickup_time_id | string | | Pickup time id |
| &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;flags | string[] | ["recommended"] | "recommended" = Shopee's suggested slot |

> **Note:** Time slots may be empty for some channels -> ship without selecting, Shopee picks timing. Prefer the `recommended` slot when auto-choosing.

---

## get_mass_shipping_parameter
`POST /api/v2/logistics/get_mass_shipping_parameter`
**Use:** Batch shipping params for multiple packages of the **same product_location_id AND logistics_channel_id**.
**Conditions:** same as get_shipping_parameter.

### Request Parameters (payload)
| Name | Type | Required | Sample | Description |
|------|------|----------|--------|-------------|
| package_list | object[] | True | | Up to 50 packages |
| &nbsp;&nbsp;package_number | string | True | OFG188728166212046 | Package id |
| logistics_channel_id | int64 | False | 50021 | Defaults to first package's channel |
| product_location_id | string | False | VN0002BIZ | Defaults to first package's warehouse |

### Response Parameters
| Name | Type | Sample | Description |
|------|------|--------|-------------|
| request_id | string | | |
| error / message | string | | |
| response | object | | |
| &nbsp;&nbsp;info_needed | object | | Same as single (dropoff/pickup/non_integrated) |
| &nbsp;&nbsp;dropoff | object | | branch_list |
| &nbsp;&nbsp;pickup | object | | address_list + time_slot_list (date, pickup_time_id, flags) |
| &nbsp;&nbsp;success_list | object[] | | Successful packages (package_number) |
| &nbsp;&nbsp;fail_list | object[] | | Failed (package_number, fail_reason) |

> **Note:** Use single get_shipping_parameter for per-order flow. Mass only for bulk-ship.

---

## ship_order
`POST /api/v2/logistics/ship_order`
**Use:** Arrange shipment -> order becomes **PROCESSED**. Call after get_shipping_parameter. Recommended >=1h after order placed.

### Request Parameters
| Name | Type | Required | Sample | Description |
|------|------|----------|--------|-------------|
| order_sn | string | True | 201212DCXHJUIKJ | Order id |
| package_number | string | False | | Package id (split) |
| pickup | object | cond | | Required if info_needed has pickup. Include even if empty |
| &nbsp;&nbsp;address_id | int64 | True | 234 | From get_shipping_parameter |
| &nbsp;&nbsp;pickup_time_id | string | False | 439291 | From get_shipping_parameter (one from time_slot_list) |
| &nbsp;&nbsp;tracking_number | string | False | | If returned in info_needed |
| dropoff | object | cond | | Required if info_needed has dropoff. Include even if empty {} |
| &nbsp;&nbsp;branch_id | int64 | False | 0 | Branch |
| &nbsp;&nbsp;sender_real_name | string | False | | Sender name |
| &nbsp;&nbsp;tracking_number | string | False | | If returned |
| &nbsp;&nbsp;slug | string | False | | Selected 3PL partner |
| non_integrated | object | cond | | If info_needed has non-integrated |
| &nbsp;&nbsp;tracking_number | string | False | AK224... | Seller's own courier tracking |

### Response Parameters
| Name | Type | Sample | Description |
|------|------|--------|-------------|
| request_id | string | | |
| error | string | | |
| message | string | | |

> **Notes:**
> - After success: pickup/dropoff -> LOGISTICS_READY -> LOGISTICS_REQUEST_CREATED; non-integrated -> immediately LOGISTICS_PICKUP_DONE.
> - **Tracking number NOT returned here.** Call get_tracking_number next.
> - Order -> **PROCESSED even though tracking number not yet generated**.
> - Carrier is buyer-chosen and fixed — you only choose pickup/dropoff details.

---

## update_shipping_order
`POST /api/v2/logistics/update_shipping_order`
**Use:** Update pickup address/time. **Pickup only.**
**Conditions:** fulfillment status LOGISTICS_PICKUP_RETRY, or LOGISTICS_REQUEST_CREATED + Instant Order Reschedule. Use when pickup wrong/failed.

### Request Parameters
| Name | Type | Required | Sample | Description |
|------|------|----------|--------|-------------|
| order_sn | string | True | 201214JASXYXY6 | Order id |
| package_number | string | False | | Package id (split) |
| pickup | object | True | | Include even if empty |
| &nbsp;&nbsp;address_id | int64 | True | 126194 | From get_shipping_parameter |
| &nbsp;&nbsp;pickup_time_id | string | True | 439291 | From get_shipping_parameter |

### Response Parameters
| Name | Type | Sample | Description |
|------|------|--------|-------------|
| request_id | string | | |
| error / message | string | | |

> **Note:** OPTIONAL / defer. Only pickup has a schedulable appointment.

---

## get_tracking_number
`GET /api/v2/logistics/get_tracking_number`
**Use:** Get the tracking number (REQUIRED for creating the waybill).

### Request Parameters
| Name | Type | Required | Sample | Description |
|------|------|----------|--------|-------------|
| order_sn | string | True | 201214JASXYXY6 | Order id |
| package_number | string | False | | Package id (split) |
| response_optional_fields | string | False | first_mile_tracking_number | plp_number, first_mile_tracking_number, last_mile_tracking_number |

### Response Parameters
| Name | Type | Sample | Description |
|------|------|--------|-------------|
| request_id | string | | |
| error / message | string | | |
| response | object | | |
| &nbsp;&nbsp;tracking_number | string | MY200448706479IT | The tracking number (MAY BE EMPTY — see note) |
| &nbsp;&nbsp;plp_number | string | | BR correios package id |
| &nbsp;&nbsp;first_mile_tracking_number | string | CNF877... | Cross-border seller |
| &nbsp;&nbsp;last_mile_tracking_number | string | 200448706479IT | Cross-border BR seller |
| &nbsp;&nbsp;hint | string | | Hint if some fields unavailable (e.g. CVS closed) |
| &nbsp;&nbsp;pickup_code | string | | ID local instant/sameday |

> **CRITICAL:** Tracking number comes from the **3PL**, so the response **CAN BE EMPTY**. **Keep polling at 5-minute intervals until it returns a value.** Or listen for `order_trackingno_push (code 4)` to avoid polling.

---

# Logistics — Waybill APIs

> Runs **per package** for split orders (one AWB per package). In create/result/download, `order_list` entries take `order_sn` + a **single** `package_number` (NOT comma-separated) — add one object per package.

## get_shipping_document_parameter
`POST /api/v2/logistics/get_shipping_document_parameter`
**Use:** Get selectable + suggested document type before creating.

### Request Parameters
| Name | Type | Required | Sample | Description |
|------|------|----------|--------|-------------|
| order_list | object[] | True | | Limit [1,50] |
| &nbsp;&nbsp;order_sn | string | True | 201201E81SYYKE | Order id |
| &nbsp;&nbsp;package_number | string | False | 60489687088750 | Package id (split). Omit (not "") for non-split |

### Response Parameters
| Name | Type | Sample | Description |
|------|------|--------|-------------|
| request_id | string | | |
| error / message | string | | |
| warning | object[] | | order_sn, package_number warnings |
| response | object | | |
| &nbsp;&nbsp;result_list | object[] | | |
| &nbsp;&nbsp;&nbsp;&nbsp;order_sn | string | 201201E81SYYKE | Order id |
| &nbsp;&nbsp;&nbsp;&nbsp;package_number | string | 60489687088750 | Package id |
| &nbsp;&nbsp;&nbsp;&nbsp;suggest_shipping_document_type | string | THERMAL_AIR_WAYBILL | Default if you omit type |
| &nbsp;&nbsp;&nbsp;&nbsp;selectable_shipping_document_type | string[] | ["THERMAL_AIR_WAYBILL"] | Allowed types for this order |
| &nbsp;&nbsp;&nbsp;&nbsp;fail_error | string | logistics.order_not_exist | Per-entry error |
| &nbsp;&nbsp;&nbsp;&nbsp;fail_message | string | | Per-entry error detail |

> **Notes:** Don't hardcode the type — varies per order/channel. Use `suggest_shipping_document_type`. Watch `logistics.can_not_print_jit_order` (channel forces Seller Center printing).

---

## create_shipping_document
`POST /api/v2/logistics/create_shipping_document`
**Use:** Start the AWB task. **Only after tracking number exists**, before LOGISTICS_PICKUP_DONE.

### Request Parameters
| Name | Type | Required | Sample | Description |
|------|------|----------|--------|-------------|
| order_list | object[] | True | | Limit [1,50] |
| &nbsp;&nbsp;order_sn | string | True | 201118BCKPJQQ8 | Order id |
| &nbsp;&nbsp;package_number | string | False | 2485710696837122445 | **Single value per object.** Split: one object per package, same order_sn |
| &nbsp;&nbsp;tracking_number | string | False | SPXID02742637123B | Required except channels allowing print-before-ship |
| &nbsp;&nbsp;shipping_document_type | string | False | NORMAL_AIR_WAYBILL | NORMAL_AIR_WAYBILL, THERMAL_AIR_WAYBILL, NORMAL_JOB_AIR_WAYBILL, THERMAL_JOB_AIR_WAYBILL, THERMAL_UNPACKAGED_LABEL |

### Response Parameters
| Name | Type | Sample | Description |
|------|------|--------|-------------|
| request_id | string | | |
| error / message | string | | |
| warning | object[] | | Per-entry warnings |
| response | object | | result_list with per-entry fail_error/fail_message |

> **Split example payload:**
> ```
> { "order_list": [
>   { "order_sn": "X", "package_number": "PKG_A", "tracking_number": "TRK_A" },
>   { "order_sn": "X", "package_number": "PKG_B", "tracking_number": "TRK_B" }
> ]}
> ```

---

## get_shipping_document_result
`POST /api/v2/logistics/get_shipping_document_result`
**Use:** Poll task status. Downloadable only when status = READY.

### Request Parameters
| Name | Type | Required | Sample | Description |
|------|------|----------|--------|-------------|
| order_list | object[] | True | | Limit [1,50] |
| &nbsp;&nbsp;order_sn | string | True | 201118BCKPJQQ8 | Order id |
| &nbsp;&nbsp;package_number | string | False | 2485710696837122445 | Package id (split) |
| &nbsp;&nbsp;tracking_number | string | False | SPXID02742637123B | Required except print-before-ship channels |
| &nbsp;&nbsp;shipping_document_type | string | False | NORMAL_AIR_WAYBILL | Same type used at create |

### Response Parameters
| Name | Type | Sample | Description |
|------|------|--------|-------------|
| request_id | string | | |
| error / message | string | | |
| warning | object[] | | order_sn, package_number |
| response | object | | |
| &nbsp;&nbsp;result_list | object[] | | |
| &nbsp;&nbsp;&nbsp;&nbsp;order_sn | string | 201118BCKPJQQ8 | Order id |
| &nbsp;&nbsp;&nbsp;&nbsp;package_number | string | 2485710696837122445 | Package id |
| &nbsp;&nbsp;&nbsp;&nbsp;status | string | READY | PROCESSING / READY / FAILED |
| &nbsp;&nbsp;&nbsp;&nbsp;fail_error | string | logistics.order_not_exist | Per-entry error |
| &nbsp;&nbsp;&nbsp;&nbsp;fail_message | string | | Per-entry error detail |

> **Note:** Status may be PROCESSING -> poll cyclically until READY (or FAILED). Or listen for `shipping_document_status_push (code 15)`.

---

## download_shipping_document
`POST /api/v2/logistics/download_shipping_document`
**Use:** Get the file. Only after result status = READY.

### Request Parameters
| Name | Type | Required | Sample | Description |
|------|------|----------|--------|-------------|
| shipping_document_type | string | False | NORMAL_AIR_WAYBILL | Same type as create |
| order_list | object[] | True | | |
| &nbsp;&nbsp;order_sn | string | True | 201118BCKPJQQ8 | Order id |
| &nbsp;&nbsp;package_number | string | False | 2485710696837122445 | Package id (split). Omit (not "") for non-split |

### Response Parameters
| Name | Type | Sample | Description |
|------|------|--------|-------------|
| waybill | file | | The waybill file |

> **Notes:**
> - Format: mostly **PDF**; TW C2C -> **HTML**; thermal-print setting -> **ZIP**. Handle non-PDF.
> - On failure returns `{ error, message, request_id }` e.g. "The package can not print now".
> - Print-failure reasons: (1) parcel dimensions missing from listing, (2) wrong buyer/seller info (address), (3) pickup address not set. Plus `can_not_print_jit_order`.

---

# Webhooks / Push

- **ONE callback URL per app** (Console or `set_push_config`). All subscribed types arrive there, distinguished by `code`.
- Respond **2xx + EMPTY body, FAST** (even for unknown codes). Slow/failed -> success rate drop -> disabled (<30% over 600+/6h; warning <70%).
- Verify Authorization header: `HMAC-SHA256(url + "|" + raw_body, partner_key)` hex. Use **raw body** (not re-encoded JSON), `hash_equals`, request URL (`fullUrl()`, with trusted proxies for https).
- Push carries identifiers only — **fetch full data via API**.

### Push types (Order Management app type)
| Code | Push | Use |
|------|------|-----|
| 1 | shop_authorization_push | Seller authorized — capture shop/merchant ids |
| 2 | shop_authorization_canceled_push | Seller revoked — deactivate shop |
| 3 | order_status_push | Order status changed |
| 4 | order_trackingno_push | Tracking number ready — **waybill trigger** |
| 12 | open_api_authorization_expiry | Auth expiring in 7 days |
| 15 | shipping_document_status_push | Document READY/FAILED — download trigger |
| 30 | package_fulfillment_status_push | Per-package status — **split handling** |

---

## order_status_push (code 3)

### Push Parameters
| Name | Type | Sample | Description |
|------|------|--------|-------------|
| data | object | | Main push message data |
| &nbsp;&nbsp;ordersn | string | 220810QSK8S7BX | Return by default. Order id |
| &nbsp;&nbsp;status | string | PROCESSED | Return by default. Current order status |
| &nbsp;&nbsp;completed_scenario | string | NORMAL | NORMAL: completed. RRAOC: return&refund after completion done |
| &nbsp;&nbsp;update_time | timestamp | 1660123127 | Return by default. Last status change |
| shop_id | int | 727720655 | Shop id |
| code | int | 3 | Push notification id |
| timestamp | timestamp | 1660123127 | Message sent time |

**status values:** PENDING, UNPAID, READY_TO_SHIP, PROCESSED, SHIPPED, TO_CONFIRM_RECEIVE, COMPLETED, RETRY_SHIP, IN_CANCEL, CANCELLED, TO_RETURN

---

## order_trackingno_push (code 4)

### Push Parameters
| Name | Type | Sample | Description |
|------|------|--------|-------------|
| data | object | | |
| &nbsp;&nbsp;ordersn | string | 220809MDBFYFT2 | Order id |
| &nbsp;&nbsp;package_number | string | OFG113701539238152 | Package id |
| &nbsp;&nbsp;tracking_no | string | BR222263688572VSPXLM71894 | Tracking number |
| shop_id | int | 296363855 | Shop id |
| code | int | 4 | Push notification id |
| timestamp | timestamp | 1660123089 | Message sent time |

> **Note:** Waybill trigger — carries `package_number`, drives per-package waybill creation for splits.

---

## package_fulfillment_status_push (code 30)

### Push Parameters
| Name | Type | Sample | Description |
|------|------|--------|-------------|
| data | object | | |
| &nbsp;&nbsp;ordersn | string | 250421TPSF33R6 | Order id |
| &nbsp;&nbsp;package_number | string | OFG198917831207390 | Package id |
| &nbsp;&nbsp;fulfillment_status | string | LOGISTICS_REQUEST_CREATED | Package fulfillment status |
| &nbsp;&nbsp;update_time | int64 | 1660123127 | Last change |
| shop_id | int64 | 727720655 | Shop id |
| code | int64 | 30 | Push notification id |
| timestamp | int64 | 1660123127 | Message sent time |

**fulfillment_status values:** LOGISTICS_NOT_START, LOGISTICS_READY, LOGISTICS_REQUEST_CREATED, LOGISTICS_PICKUP_DONE, LOGISTICS_DELIVERY_DONE, LOGISTICS_PICKUP_RETRY, LOGISTICS_INVALID, LOGISTICS_REQUEST_CANCELED, LOGISTICS_PICKUP_FAILED, LOGISTICS_DELIVERY_FAILED, LOGISTICS_LOST

---

## open_api_authorization_expiry (code 12)

### Push Parameters
| Name | Type | Sample | Description |
|------|------|--------|-------------|
| code | int32 | 12 | Push notification id |
| timestamp | timestamp | 1568606634 | Message sent time |
| data | object[] | | |
| &nbsp;&nbsp;merchant_expire_soon | int64[] | [123123,4342] | Merchant ids expiring within one week |
| &nbsp;&nbsp;shop_expire_soon | int64[] | [23213,243242] | Shop ids expiring within one week |
| &nbsp;&nbsp;user_expire_soon | int64[] | [368765104] | User ids expiring within one week |
| &nbsp;&nbsp;expire_before | timestamp | 1619740800 | Pushed ids expire before this time |
| &nbsp;&nbsp;page_no | int32 | 1 | Page number |
| &nbsp;&nbsp;total_page | int32 | 2 | Total pages |

---

# Critical Rules & Gotchas

### Status / flow rules
- **READY_TO_SHIP** is the only status you can ship from.
- **PROCESSED fires before the tracking number exists** — trigger waybill off tracking-ready (code 4 / get_tracking_number returns), NOT PROCESSED.
- **Tracking number from 3PL can be empty** — poll at 5-min intervals until populated.
- **PENDING is a real verification gate** — can't ship until cleared.
- **create_shipping_document needs the tracking number** and must run **before** LOGISTICS_PICKUP_DONE.
- **download** only works when result = READY.
- **Carrier is buyer-chosen and fixed** — you only choose pickup/dropoff details.
- **is_shipment_arranged=true** -> already processing, don't double-ship.

### Signing
- Shop API base string: `partner_id + path + timestamp + access_token + shop_id` (5-part).
- Public (token get/refresh): `partner_id + path + timestamp` (3-part).
- Push verify base string: `url + "|" + raw_body`.
- Always `hash_hmac('sha256', ...)` — **never '256'**. Timestamp valid 5 min.

### Recurring code bugs (review checklist)
- `hash_hmac('sha256', ...)` — not '256'.
- Read every `if` guard aloud vs intent (inversion bugs).
- After any rename, grep the old name (`name`->`provider_type`, `account_id`/`provider_id`).
- Auth endpoints sign PUBLIC (3-part), not Shop (5-part).
- `readonly` props can't be reassigned (token mutation).

### Multi-package / waybill
- **One AWB per package.** Split -> loop packages, one document each.
- In create/result/download, `package_number` is a **single value per object** — add multiple objects for multiple packages (same order_sn).
- `get_shipping_parameter` / `ship_order` / `get_tracking_number` key off `order_sn` (+ optional `package_number`).

---

# Unverified — Test in Sandbox

1. **Split-order status aggregation** — what `order_status` becomes when packages diverge. Use `package_fulfillment_status_push (code 30)` per package; don't rely on order-level status for splits.
2. **Per-package AWB API mechanics** — confirm create/download return a separate document per `package_number` (structure + official seller docs say one AWB per parcel).
3. **Tracking timing after PROCESSED** — confirmed PROCESSED fires before tracking; verify lag and that polling resolves it.
4. **Shared refresh_token once-per-shop_id** — for main-account fan-out (auth).

### Hosts
- **Sandbox API:** `https://openplatform.sandbox.test-stable.shopee.sg`
- **Sandbox Auth:** `https://open.test-stable.shopee.com`
- **Prod API (Global):** `https://partner.shopeemobile.com`
- **Prod Auth:** `https://open.shopee.com`
