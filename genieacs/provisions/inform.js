/**
 * GENIEACS DEFAULT INFORM PROVISION SCRIPT
 * Dijalankan otomatis setiap kali ONT mengirimkan sinyal INFORM (Boot / Periodic)
 */

const now = Date.now();

// 1. Pastikan ONT mengaktifkan Periodic Inform setiap 300 detik (5 menit)
declare("InternetGatewayDevice.ManagementServer.PeriodicInformEnable", {path: now, value: now}, {value: true});
declare("InternetGatewayDevice.ManagementServer.PeriodicInformInterval", {path: now, value: now}, {value: 300});

// Device:2 (TR-181) support jika modem menggunakan data model baru
declare("Device.ManagementServer.PeriodicInformEnable", {path: now, value: now}, {value: true});
declare("Device.ManagementServer.PeriodicInformInterval", {path: now, value: now}, {value: 300});

// 2. Ambil Informasi Dasar Perangkat
declare("InternetGatewayDevice.DeviceInfo.Manufacturer", {value: 1});
declare("InternetGatewayDevice.DeviceInfo.ModelName", {value: 1});
declare("InternetGatewayDevice.DeviceInfo.SoftwareVersion", {value: 1});
declare("InternetGatewayDevice.DeviceInfo.HardwareVersion", {value: 1});
declare("InternetGatewayDevice.DeviceInfo.UpTime", {value: 1});

// 3. Ambil Informasi Redaman Optik (RX / TX Power)
// A. Standard TR-098 / ZTE Optical Parameters
declare("InternetGatewayDevice.WANDevice.*.WANEponInterfaceConfig.RXPower", {value: 1});
declare("InternetGatewayDevice.WANDevice.*.WANGponInterfaceConfig.RXPower", {value: 1});
declare("InternetGatewayDevice.WANDevice.*.WANGponInterfaceConfig.TXPower", {value: 1});
declare("InternetGatewayDevice.WANDevice.*.X_ZTE-COM_WANPONInterfaceConfig.RXPower", {value: 1});
declare("InternetGatewayDevice.WANDevice.*.X_ZTE-COM_WANPONInterfaceConfig.TXPower", {value: 1});

// B. Huawei Optical Parameters
declare("InternetGatewayDevice.WANDevice.*.X_HW_Optical.RxOpticalPower", {value: 1});
declare("InternetGatewayDevice.WANDevice.*.X_HW_Optical.TxOpticalPower", {value: 1});

// C. Fiberhome Optical Parameters
declare("InternetGatewayDevice.WANDevice.*.X_FH_PON.RxPower", {value: 1});
declare("InternetGatewayDevice.WANDevice.*.X_FH_PON.TxPower", {value: 1});

// 4. Ambil Status Wi-Fi & SSID saat ini
declare("InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID", {value: 1});
declare("InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.Enable", {value: 1});
declare("InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.TotalAssociations", {value: 1});

// 5. Ambil Status WAN IP & Koneksi PPPoE
declare("InternetGatewayDevice.WANDevice.*.WANConnectionDevice.*.WANPPPConnection.*.ExternalIPAddress", {value: 1});
declare("InternetGatewayDevice.WANDevice.*.WANConnectionDevice.*.WANPPPConnection.*.ConnectionStatus", {value: 1});
declare("InternetGatewayDevice.WANDevice.*.WANConnectionDevice.*.WANPPPConnection.*.Username", {value: 1});
