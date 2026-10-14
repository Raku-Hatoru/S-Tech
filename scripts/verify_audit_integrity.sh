#!/usr/bin/env bash
set -euo pipefail

PORT="${PORT:-8099}"
BASE_URL="http://127.0.0.1:${PORT}"
COOKIE_JAR=$(mktemp)
RESP_FILE=$(mktemp)
SERVER_PID=""

cleanup() {
    rm -f "${COOKIE_JAR}" "${RESP_FILE}"
    if [[ -n "${SERVER_PID}" ]]; then
        kill "${SERVER_PID}" 2>/dev/null || true
    fi
}
trap cleanup EXIT

CREDENTIALS_FILE=".audit-credentials"
DISTRIB_EMAIL=$(grep -A 2 "\[Distributor\]" "${CREDENTIALS_FILE}" | grep "Email:" | head -n 1 | awk '{print $2}')
DISTRIB_PASSWORD=$(grep -A 3 "\[Distributor\]" "${CREDENTIALS_FILE}" | grep "Password:" | head -n 1 | awk '{print $2}')
BUMDES_EMAIL=$(grep -A 2 "\[BUMDes\]" "${CREDENTIALS_FILE}" | grep "Email:" | head -n 1 | awk '{print $2}')
BUMDES_PASSWORD=$(grep -A 3 "\[BUMDes\]" "${CREDENTIALS_FILE}" | grep "Password:" | head -n 1 | awk '{print $2}')
ADMIN_EMAIL=$(grep -A 2 "\[Admin\]" "${CREDENTIALS_FILE}" | grep "Email:" | head -n 1 | awk '{print $2}')
ADMIN_PASSWORD=$(grep -A 3 "\[Admin\]" "${CREDENTIALS_FILE}" | grep "Password:" | head -n 1 | awk '{print $2}')

php artisan cache:clear >/dev/null 2>&1 || true

if ! curl -s -f -o /dev/null "${BASE_URL}/peta"; then
    php -S "127.0.0.1:${PORT}" -t public >/dev/null 2>&1 &
    SERVER_PID=$!
    sleep 1.5
fi

get_csrf() {
    curl -s -c "${COOKIE_JAR}" "${BASE_URL}/login" | grep -o 'name="_token" value="[^"]*"' | head -n1 | cut -d'"' -f4
}

echo "======================================================================"
echo "AUDIT.MD INTEGRITY REGRESSION CHECK"
echo "Target: ${BASE_URL}"
echo "======================================================================"

ALL_PASS=true

# 1. Guest Check
echo ""
echo "--- 1. Hak Akses & UI Tamu (Guest / Tanpa Login) ---"
curl -s "${BASE_URL}/peta" > "${RESP_FILE}"

check() {
    local label="$1"
    local condition="$2"
    if eval "${condition}"; then
        printf "  [PASS] %s\n" "${label}"
    else
        printf "  [FAIL] %s\n" "${label}"
        ALL_PASS=false
    fi
}

check "Mode Publik badge di header" "grep -q 'Mode Publik' ${RESP_FILE}"
check "Tombol Masuk di header" "grep -q 'Masuk' ${RESP_FILE}"
check "Portal Pengiriman TERSEMBUNYI" "! grep -q 'Portal Pengiriman' ${RESP_FILE}"
check "Portal BUMDes TERSEMBUNYI" "! grep -q 'Portal BUMDes' ${RESP_FILE}"
check "Panel Admin TERSEMBUNYI" "! grep -q 'Panel Admin' ${RESP_FILE}"
check "Disposisi Logistik TERSEMBUNYI di peta" "! grep -q 'Disposisi Logistik' ${RESP_FILE}"

curl -s "${BASE_URL}/wilayah/3302" > "${RESP_FILE}"
check "Tombol Buat DO TERSEMBUNYI di detail wilayah" "! grep -q '▶ Buat DO' ${RESP_FILE}"
check "Panggil Operator Logistik TERSEMBUNYI di detail wilayah" "! grep -q 'Panggil Operator Logistik' ${RESP_FILE}"

# 2. Distributor Check
echo ""
echo "--- 2. Hak Akses & UI Distributor ---"
rm -f "${COOKIE_JAR}"
TOKEN=$(get_csrf)
curl -s -b "${COOKIE_JAR}" -c "${COOKIE_JAR}" -X POST "${BASE_URL}/login" \
    --data-urlencode "_token=${TOKEN}" \
    --data-urlencode "email=${DISTRIB_EMAIL}" \
    --data-urlencode "password=${DISTRIB_PASSWORD}" > /dev/null

curl -s -b "${COOKIE_JAR}" "${BASE_URL}/peta" > "${RESP_FILE}"
check "Portal Pengiriman TAMPIL di navbar" "grep -q 'Portal Pengiriman' ${RESP_FILE}"
check "Tombol Disposisi Logistik TAMPIL di peta" "grep -q 'Disposisi Logistik' ${RESP_FILE}"
check "Portal BUMDes TERSEMBUNYI" "! grep -q 'Portal BUMDes' ${RESP_FILE}"
check "Panel Admin TERSEMBUNYI" "! grep -q 'Panel Admin' ${RESP_FILE}"
check "Peran Distributor terdeteksi di auth header" "grep -qi 'Distributor' ${RESP_FILE}"

curl -s -b "${COOKIE_JAR}" "${BASE_URL}/wilayah/3302" > "${RESP_FILE}"
check "Tombol Buat DO TAMPIL di detail wilayah" "grep -q '▶ Buat DO' ${RESP_FILE}"

# 3. BUMDes Check
echo ""
echo "--- 3. Hak Akses & UI BUMDes ---"
rm -f "${COOKIE_JAR}"
TOKEN=$(get_csrf)
curl -s -b "${COOKIE_JAR}" -c "${COOKIE_JAR}" -X POST "${BASE_URL}/login" \
    --data-urlencode "_token=${TOKEN}" \
    --data-urlencode "email=${BUMDES_EMAIL}" \
    --data-urlencode "password=${BUMDES_PASSWORD}" > /dev/null

curl -s -b "${COOKIE_JAR}" "${BASE_URL}/peta" > "${RESP_FILE}"
check "Portal BUMDes TAMPIL di navbar" "grep -q 'Portal BUMDes' ${RESP_FILE}"
check "Portal Pengiriman TERSEMBUNYI" "! grep -q 'Portal Pengiriman' ${RESP_FILE}"
check "Panel Admin TERSEMBUNYI" "! grep -q 'Panel Admin' ${RESP_FILE}"
check "Tombol Disposisi Logistik TERSEMBUNYI" "! grep -q 'Disposisi Logistik' ${RESP_FILE}"
check "Peran BUMDes terdeteksi di auth header" "grep -qi 'BUMDes' ${RESP_FILE}"

# 4. Administrator Check
echo ""
echo "--- 4. Hak Akses & UI Administrator ---"
rm -f "${COOKIE_JAR}"
TOKEN=$(get_csrf)
curl -s -b "${COOKIE_JAR}" -c "${COOKIE_JAR}" -X POST "${BASE_URL}/login" \
    --data-urlencode "_token=${TOKEN}" \
    --data-urlencode "email=${ADMIN_EMAIL}" \
    --data-urlencode "password=${ADMIN_PASSWORD}" > /dev/null

curl -s -b "${COOKIE_JAR}" "${BASE_URL}/peta" > "${RESP_FILE}"
check "Portal Pengiriman TAMPIL di navbar" "grep -q 'Portal Pengiriman' ${RESP_FILE}"
check "Portal BUMDes TAMPIL di navbar" "grep -q 'Portal BUMDes' ${RESP_FILE}"
check "Panel Admin TAMPIL di navbar" "grep -q 'Panel Admin' ${RESP_FILE}"
check "Tombol Disposisi Logistik TAMPIL di peta" "grep -q 'Disposisi Logistik' ${RESP_FILE}"
check "Peran Administrator terdeteksi di auth header" "grep -qi 'Admin' ${RESP_FILE}"

# 5. Public Receipt Numbers Check
echo ""
echo "--- 5. Kerahasiaan Nomor Resi pada Halaman Publik ---"
curl -s "${BASE_URL}/peta" > "${RESP_FILE}"
check "Halaman /peta bebas nomor resi DO-/SHP-" "! grep -qiE '\b(DO-[A-Z0-9]+|SHP-[A-Z0-9]+)\b' ${RESP_FILE}"

curl -s "${BASE_URL}/wilayah/3302" > "${RESP_FILE}"
check "Halaman /wilayah/3302 bebas nomor resi DO-/SHP-" "! grep -qiE '\b(DO-[A-Z0-9]+|SHP-[A-Z0-9]+)\b' ${RESP_FILE}"

# 6. No Demo Credentials on Login Page
echo ""
echo "--- 6. Ketiadaan Kredensial Demo pada /login ---"
curl -s "${BASE_URL}/login" > "${RESP_FILE}"
check "Halaman /login bebas email demo" "! grep -qiE '(admin@sipangan|distributor@sipangan|bumdes@sipangan)' ${RESP_FILE}"
check "Halaman /login bebas kata sandi/petunjuk demo" "! grep -qiE '(password123|Kredensial Demo|Akun Percobaan)' ${RESP_FILE}"

echo ""
echo "======================================================================"
if [ "${ALL_PASS}" = true ]; then
    echo "STATUS: SEMUA ATURAN AUDIT.MD TERVERIFIKASI TERJAGA (100% PASS)"
else
    echo "STATUS: TERDAPAT KEGAGALAN AUDIT.MD"
    exit 1
fi
echo "======================================================================"
