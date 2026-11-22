/**
 * GENIEACS WI-FI CONFIGURATION PROVISION SCRIPT
 * Mengatur SSID dan Password Wi-Fi WPA2-PSK pada ONT
 * Nilai SSID dan Password dikirimkan dari NetSpeed ISP Billing melalui API NBI
 */

const now = Date.now();

// 1. Ambil target SSID & Password dari tag/parameter atau task
// Default index radio 1 = Wi-Fi 2.4 GHz
const wlanPath = "InternetGatewayDevice.LANDevice.1.WLANConfiguration.1";

// Pastikan Wi-Fi Aktif
declare(wlanPath + ".Enable", {path: now, value: now}, {value: true});

// Atur Mode Enkripsi ke WPA2-PSK (AES)
declare(wlanPath + ".BeaconType", {path: now, value: now}, {value: "11i"});
declare(wlanPath + ".WPAEncryptionModes", {path: now, value: now}, {value: "AESEncryption"});
declare(wlanPath + ".IEEE11iEncryptionModes", {path: now, value: now}, {value: "AESEncryption"});
declare(wlanPath + ".WPAAuthenticationMode", {path: now, value: now}, {value: "PSKAuthentication"});
declare(wlanPath + ".IEEE11iAuthenticationMode", {path: now, value: now}, {value: "PSKAuthentication"});

// Contoh set SSID & Password jika dispesifikasikan:
// declare(wlanPath + ".SSID", {path: now, value: now}, {value: "NetSpeed_Pelanggan"});
// declare(wlanPath + ".PreSharedKey.1.PreSharedKey", {path: now, value: now}, {value: "password123"});
