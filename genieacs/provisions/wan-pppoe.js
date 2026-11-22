/**
 * GENIEACS WAN PPPoE AUTO-PROVISIONING SCRIPT
 * Mengonfigurasi WAN Connection Device bertipe PPPoE dengan VLAN Tag
 */

const now = Date.now();

// Menemukan atau membuat instance WAN PPP Connection
const wanPppPath = "InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1";

// 1. Pastikan koneksi WAN diaktifkan
declare(wanPppPath + ".Enable", {path: now, value: now}, {value: true});
declare(wanPppPath + ".ConnectionType", {path: now, value: now}, {value: "IP_Routed"});
declare(wanPppPath + ".NATEnabled", {path: now, value: now}, {value: true});

// 2. Kredensial PPPoE (diisi via API atau parameter tags)
// declare(wanPppPath + ".Username", {path: now, value: now}, {value: "user_pppoe"});
// declare(wanPppPath + ".Password", {path: now, value: now}, {value: "pass_pppoe"});

// 3. Konfigurasi VLAN (Contoh: VLAN 100 untuk Internet)
// declare("InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.X_ZTE-COM_VLANIDMark", {path: now, value: now}, {value: 100});
// declare("InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.X_HW_VLAN", {path: now, value: now}, {value: 100});
