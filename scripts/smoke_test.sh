#!/usr/bin/env bash
# ==============================================================================
# Repeatable Smoke Test Script for PRD Demo Flow (Section 16)
# SIPANGAN WASPADA - Verified Cleanup Audit Baseline
# ==============================================================================
set -euo pipefail

PORT=${SMOKE_PORT:-8099}
BASE_URL="http://127.0.0.1:${PORT}"
COOKIE_JAR=$(mktemp /tmp/sipangan_cookies_XXXXXX.txt)
RESPONSE_BODY=$(mktemp /tmp/sipangan_resp_XXXXXX.html)
RESPONSE_HEADERS=$(mktemp /tmp/sipangan_headers_XXXXXX.txt)
CREDENTIALS_FILE=".audit-credentials"

cleanup() {
    if [[ -n "${SERVER_PID:-}" ]] && kill -0 "${SERVER_PID}" 2>/dev/null; then
        kill "${SERVER_PID}" 2>/dev/null || true
    fi
    rm -f "${COOKIE_JAR}" "${RESPONSE_BODY}" "${RESPONSE_HEADERS}"
}
trap cleanup EXIT

echo "======================================================================"
echo "SIPANGAN WASPADA - SMOKE TEST (PRD Demo Flow Section 16)"
echo "Base URL: ${BASE_URL}"
echo "======================================================================"

# 1. Parse credentials safely without printing passwords to stdout
if [[ ! -f "${CREDENTIALS_FILE}" ]]; then
    echo "[ERROR] File ${CREDENTIALS_FILE} tidak ditemukan!"
    exit 1
fi

DISTRIB_EMAIL=$(grep -A 2 "\[Distributor\]" "${CREDENTIALS_FILE}" | grep "Email:" | head -n 1 | awk '{print $2}')
DISTRIB_PASSWORD=$(grep -A 3 "\[Distributor\]" "${CREDENTIALS_FILE}" | grep "Password:" | head -n 1 | awk '{print $2}')

BUMDES_EMAIL=$(grep -A 2 "\[BUMDes\]" "${CREDENTIALS_FILE}" | grep "Email:" | head -n 1 | awk '{print $2}')
BUMDES_PASSWORD=$(grep -A 3 "\[BUMDes\]" "${CREDENTIALS_FILE}" | grep "Password:" | head -n 1 | awk '{print $2}')

ADMIN_EMAIL=$(grep -A 2 "\[Admin\]" "${CREDENTIALS_FILE}" | grep "Email:" | head -n 1 | awk '{print $2}')
ADMIN_PASSWORD=$(grep -A 3 "\[Admin\]" "${CREDENTIALS_FILE}" | grep "Password:" | head -n 1 | awk '{print $2}')

# 2. Start local web server
echo "[INFO] Menjalankan ephemeral PHP built-in web server pada port ${PORT}..."
php -S "127.0.0.1:${PORT}" -t public > /dev/null 2>&1 &
SERVER_PID=$!
sleep 1.5

if ! kill -0 "${SERVER_PID}" 2>/dev/null; then
    echo "[FAIL] Ephemeral web server gagal berjalan."
    exit 1
fi
echo "[INFO] Server aktif (PID: ${SERVER_PID})."

# Helper function to get CSRF token
get_csrf_token() {
    local url="$1"
    local token
    token=$(curl -s -b "${COOKIE_JAR}" -c "${COOKIE_JAR}" "${url}" | grep -o 'name="_token" value="[^"]*"' | head -n 1 | cut -d'"' -f4 || echo "")
    if [[ -z "${token}" ]]; then
        # Try meta tag
        token=$(curl -s -b "${COOKIE_JAR}" -c "${COOKIE_JAR}" "${url}" | grep -o 'name="csrf-token" content="[^"]*"' | head -n 1 | cut -d'"' -f4 || echo "")
    fi
    echo "${token}"
}

# Results tracking
declare -a STEP_NAMES
declare -a STEP_RESULTS
declare -a STEP_DETAILS

record_step() {
    local name="$1"
    local status="$2"
    local detail="$3"
    STEP_NAMES+=("${name}")
    STEP_RESULTS+=("${status}")
    STEP_DETAILS+=("${detail}")
    printf "  %-40s [%s] %s\n" "${name}" "${status}" "${detail}"
}

echo ""
echo "--- Menjalankan Langkah Uji Demo Flow ---"

# ------------------------------------------------------------------------------
# Langkah 1: Peta publik termuat tanpa login (GET /peta)
# ------------------------------------------------------------------------------
HTTP_CODE=$(curl -s -o "${RESPONSE_BODY}" -w "%{http_code}" "${BASE_URL}/peta")
if [[ "${HTTP_CODE}" == "200" ]] && grep -q "Peta Heatmap" "${RESPONSE_BODY}"; then
    record_step "1. Peta Publik (GET /peta)" "PASS" "HTTP 200, Judul Peta terdeteksi (tanpa login)"
else
    record_step "1. Peta Publik (GET /peta)" "FAIL" "HTTP ${HTTP_CODE}"
fi

# ------------------------------------------------------------------------------
# Langkah 2: Detail wilayah termuat tanpa login (GET /wilayah/3302)
# ------------------------------------------------------------------------------
HTTP_CODE=$(curl -s -o "${RESPONSE_BODY}" -w "%{http_code}" "${BASE_URL}/wilayah/3302")
if [[ "${HTTP_CODE}" == "200" ]] && grep -qi "Banyumas" "${RESPONSE_BODY}"; then
    record_step "2. Detail Wilayah Banyumas (GET /wilayah/3302)" "PASS" "HTTP 200, Info Banyumas terdeteksi"
else
    record_step "2. Detail Wilayah Banyumas (GET /wilayah/3302)" "FAIL" "HTTP ${HTTP_CODE}"
fi

# ------------------------------------------------------------------------------
# Langkah 3: Login sebagai Distributor
# ------------------------------------------------------------------------------
rm -f "${COOKIE_JAR}"
TOKEN=$(get_csrf_token "${BASE_URL}/login")
HTTP_CODE=$(curl -s -b "${COOKIE_JAR}" -c "${COOKIE_JAR}" -o "${RESPONSE_BODY}" -w "%{http_code}" -X POST "${BASE_URL}/login" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    --data-urlencode "_token=${TOKEN}" \
    --data-urlencode "email=${DISTRIB_EMAIL}" \
    --data-urlencode "password=${DISTRIB_PASSWORD}")

DISTRIB_LOGIN_OK=false
if [[ "${HTTP_CODE}" == "302" ]]; then
    DISTRIB_LOGIN_OK=true
    record_step "3. Login Distributor (POST /login)" "PASS" "HTTP 302 Redirect ke dashboard distributor"
else
    ERR_MSG="HTTP ${HTTP_CODE}"
    if grep -q "AuditService" "${RESPONSE_BODY}"; then
        ERR_MSG="HTTP ${HTTP_CODE} (Class AuditService not found)"
    fi
    record_step "3. Login Distributor (POST /login)" "FAIL" "${ERR_MSG}"
fi

# ------------------------------------------------------------------------------
# Langkah 4: Buat Pengiriman (Distributor)
# ------------------------------------------------------------------------------
if [[ "${DISTRIB_LOGIN_OK}" == "true" ]]; then
    HTTP_CODE=$(curl -s -b "${COOKIE_JAR}" -c "${COOKIE_JAR}" -o "${RESPONSE_BODY}" -w "%{http_code}" "${BASE_URL}/pengiriman/buat")
    if [[ "${HTTP_CODE}" == "200" ]]; then
        record_step "4. Form Buat Pengiriman (GET /pengiriman/buat)" "PASS" "HTTP 200, Form aktif"
    else
        record_step "4. Form Buat Pengiriman (GET /pengiriman/buat)" "FAIL" "HTTP ${HTTP_CODE}"
    fi
else
    record_step "4. Form Buat Pengiriman" "BLOCKED" "Terblokir karena login distributor gagal"
fi

# ------------------------------------------------------------------------------
# Langkah 5: Lacak Resi Pengiriman
# ------------------------------------------------------------------------------
if [[ "${DISTRIB_LOGIN_OK}" == "true" ]]; then
    HTTP_CODE=$(curl -s -b "${COOKIE_JAR}" -c "${COOKIE_JAR}" -o "${RESPONSE_BODY}" -w "%{http_code}" "${BASE_URL}/pengiriman")
    if [[ "${HTTP_CODE}" == "200" ]]; then
        record_step "5. Dashboard Tracking Resi (GET /pengiriman)" "PASS" "HTTP 200, Daftar pengiriman terbuka"
    else
        record_step "5. Dashboard Tracking Resi (GET /pengiriman)" "FAIL" "HTTP ${HTTP_CODE}"
    fi
else
    record_step "5. Dashboard Tracking Resi" "BLOCKED" "Terblokir karena login distributor gagal"
fi

# ------------------------------------------------------------------------------
# Langkah 6: Login sebagai BUMDes
# ------------------------------------------------------------------------------
rm -f "${COOKIE_JAR}"
TOKEN=$(get_csrf_token "${BASE_URL}/login")
HTTP_CODE=$(curl -s -b "${COOKIE_JAR}" -c "${COOKIE_JAR}" -o "${RESPONSE_BODY}" -w "%{http_code}" -X POST "${BASE_URL}/login" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    --data-urlencode "_token=${TOKEN}" \
    --data-urlencode "email=${BUMDES_EMAIL}" \
    --data-urlencode "password=${BUMDES_PASSWORD}")

BUMDES_LOGIN_OK=false
if [[ "${HTTP_CODE}" == "302" ]]; then
    BUMDES_LOGIN_OK=true
    record_step "6. Login BUMDes (POST /login)" "PASS" "HTTP 302 Redirect ke modul panen"
else
    ERR_MSG="HTTP ${HTTP_CODE}"
    if grep -q "AuditService" "${RESPONSE_BODY}"; then
        ERR_MSG="HTTP ${HTTP_CODE} (Class AuditService not found)"
    fi
    record_step "6. Login BUMDes (POST /login)" "FAIL" "${ERR_MSG}"
fi

# ------------------------------------------------------------------------------
# Langkah 7: Submit Hasil Panen & Sisa Stok (BUMDes)
# ------------------------------------------------------------------------------
if [[ "${BUMDES_LOGIN_OK}" == "true" ]]; then
    TOKEN=$(get_csrf_token "${BASE_URL}/panen")
    HTTP_CODE=$(curl -s -b "${COOKIE_JAR}" -c "${COOKIE_JAR}" -o "${RESPONSE_BODY}" -w "%{http_code}" -X POST "${BASE_URL}/panen" \
        -H "Content-Type: application/x-www-form-urlencoded" \
        --data-urlencode "_token=${TOKEN}" \
        --data-urlencode "komoditas=beras" \
        --data-urlencode "periode=2026-03" \
        --data-urlencode "panen_kg=1500" \
        --data-urlencode "sisa_stok_kg=400")
    if [[ "${HTTP_CODE}" == "302" ]]; then
        record_step "7. Submit Hasil Panen & Sisa Stok" "PASS" "HTTP 302 Sukses tersimpan"
    else
        record_step "7. Submit Hasil Panen & Sisa Stok" "FAIL" "HTTP ${HTTP_CODE}"
    fi
else
    record_step "7. Submit Hasil Panen & Sisa Stok" "BLOCKED" "Terblokir karena login BUMDes gagal"
fi

# ------------------------------------------------------------------------------
# Langkah 8: Lihat Rekap Panen (BUMDes)
# ------------------------------------------------------------------------------
if [[ "${BUMDES_LOGIN_OK}" == "true" ]]; then
    HTTP_CODE=$(curl -s -b "${COOKIE_JAR}" -c "${COOKIE_JAR}" -o "${RESPONSE_BODY}" -w "%{http_code}" "${BASE_URL}/panen")
    if [[ "${HTTP_CODE}" == "200" ]]; then
        record_step "8. Rekap Panen BUMDes (GET /panen)" "PASS" "HTTP 200, Halaman rekap termuat"
    else
        record_step "8. Rekap Panen BUMDes (GET /panen)" "FAIL" "HTTP ${HTTP_CODE}"
    fi
else
    record_step "8. Rekap Panen BUMDes" "BLOCKED" "Terblokir karena login BUMDes gagal"
fi

# ------------------------------------------------------------------------------
# Langkah 9: Rekomendasi Jadwal Tanam (BUMDes)
# ------------------------------------------------------------------------------
if [[ "${BUMDES_LOGIN_OK}" == "true" ]]; then
    HTTP_CODE=$(curl -s -b "${COOKIE_JAR}" -c "${COOKIE_JAR}" -o "${RESPONSE_BODY}" -w "%{http_code}" "${BASE_URL}/jadwal-tanam")
    if [[ "${HTTP_CODE}" == "200" ]]; then
        record_step "9. Jadwal Tanam (GET /jadwal-tanam)" "PASS" "HTTP 200, Simulasi jadwal tanam tampil"
    else
        record_step "9. Jadwal Tanam (GET /jadwal-tanam)" "FAIL" "HTTP ${HTTP_CODE}"
    fi
else
    record_step "9. Jadwal Tanam" "BLOCKED" "Terblokir karena login BUMDes gagal"
fi

# ------------------------------------------------------------------------------
# Langkah 10: Login sebagai Admin
# ------------------------------------------------------------------------------
rm -f "${COOKIE_JAR}"
TOKEN=$(get_csrf_token "${BASE_URL}/login")
HTTP_CODE=$(curl -s -b "${COOKIE_JAR}" -c "${COOKIE_JAR}" -o "${RESPONSE_BODY}" -w "%{http_code}" -X POST "${BASE_URL}/login" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    --data-urlencode "_token=${TOKEN}" \
    --data-urlencode "email=${ADMIN_EMAIL}" \
    --data-urlencode "password=${ADMIN_PASSWORD}")

ADMIN_LOGIN_OK=false
if [[ "${HTTP_CODE}" == "302" ]]; then
    ADMIN_LOGIN_OK=true
    record_step "10. Login Admin (POST /login)" "PASS" "HTTP 302 Redirect ke panel admin"
else
    ERR_MSG="HTTP ${HTTP_CODE}"
    if grep -q "AuditService" "${RESPONSE_BODY}"; then
        ERR_MSG="HTTP ${HTTP_CODE} (Class AuditService not found)"
    fi
    record_step "10. Login Admin (POST /login)" "FAIL" "${ERR_MSG}"
fi

# ------------------------------------------------------------------------------
# Langkah 11: Akses Halaman-halaman Admin
# ------------------------------------------------------------------------------
if [[ "${ADMIN_LOGIN_OK}" == "true" ]]; then
    ADMIN_PAGES=("/admin/pengguna" "/admin/dataset" "/admin/forecast" "/admin/pengaturan")
    ALL_ADMIN_PASS=true
    for page in "${ADMIN_PAGES[@]}"; do
        P_CODE=$(curl -s -b "${COOKIE_JAR}" -c "${COOKIE_JAR}" -o "${RESPONSE_BODY}" -w "%{http_code}" "${BASE_URL}${page}")
        if [[ "${P_CODE}" != "200" ]]; then
            ALL_ADMIN_PASS=false
        fi
    done
    if [[ "${ALL_ADMIN_PASS}" == "true" ]]; then
        record_step "11. Panel Admin (4 Halaman Utama)" "PASS" "Semua halaman admin HTTP 200"
    else
        record_step "11. Panel Admin (4 Halaman Utama)" "FAIL" "Sebagian rute admin tidak merespons 200"
    fi
else
    record_step "11. Panel Admin (4 Halaman Utama)" "BLOCKED" "Terblokir karena login Admin gagal"
fi

echo ""
echo "======================================================================"
echo "RINGKASAN SMOKE TEST BASELINE"
echo "======================================================================"
PASS_CNT=0
FAIL_CNT=0
BLOCKED_CNT=0

for i in "${!STEP_NAMES[@]}"; do
    res="${STEP_RESULTS[$i]}"
    if [[ "${res}" == "PASS" ]]; then
        ((PASS_CNT++)) || true
    elif [[ "${res}" == "FAIL" ]]; then
        ((FAIL_CNT++)) || true
    elif [[ "${res}" == "BLOCKED" ]]; then
        ((BLOCKED_CNT++)) || true
    fi
    printf "%-45s : %s (%s)\n" "${STEP_NAMES[$i]}" "${STEP_RESULTS[$i]}" "${STEP_DETAILS[$i]}"
done

echo "----------------------------------------------------------------------"
echo "Total Langkah : ${#STEP_NAMES[@]}"
echo "PASS          : ${PASS_CNT}"
echo "FAIL          : ${FAIL_CNT}"
echo "BLOCKED       : ${BLOCKED_CNT}"
echo ""
echo "Catatan Verifikasi Runtime:"
echo "- Pengujian interaktivitas JavaScript (klik peta Leaflet, render Chart.js canvas) diklasifikasikan sebagai: NOT RUNTIME-VERIFIED VIA HEADLESS BROWSER (Headless browser tidak tersedia di environment; verifikasi runtime level HTTP menggunakan curl cookie-jar dan ekstraksi CSRF token)."
echo "======================================================================"
