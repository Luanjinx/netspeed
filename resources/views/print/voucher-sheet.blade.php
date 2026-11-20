<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Lembar Voucher - {{ $batch ?? 'Voucher' }}</title>
    <style>
        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            font-family: Tahoma, Arial, sans-serif;
            background: #f1f5f9;
            margin: 0;
            padding: 10px;
        }
        .qrcode {
            height: 60px;
            width: 60px;
        }
        .sheet-container {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
            justify-content: flex-start;
            background: #ffffff;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .btn-print {
            background: #2563eb;
            color: #ffffff;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
            margin-bottom: 15px;
        }
        .btn-print:hover {
            background: #1d4ed8;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: #ffffff;
                padding: 0;
                margin: 0;
            }
            .sheet-container {
                box-shadow: none;
                padding: 0;
                gap: 2px;
            }
            @page {
                margin: 5mm;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding: 0 5px;">
        <div>
            <h2 style="margin: 0; font-size: 18px; font-weight: bold; color: #1e293b;">
                Lembar Cetak Voucher Hotspot
            </h2>
            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                Total: <strong>{{ count($vouchers) }}</strong> Voucher | Batch: <strong>{{ $batch ?? '-' }}</strong>
            </div>
        </div>
        <button class="btn-print" onclick="window.print()">
            🖨️ Cetak Lembar Voucher (A4 / F4)
        </button>
    </div>

    @php
        $qrRenderer = new \chillerlan\QRCode\QRCode();
        $csPhone = \App\Models\IspSetting::get('company_phone', '0822 4072 4572');
        $hotspotName = \App\Models\IspSetting::get('hotspot_name', 'MKS');
        $hotspotDns = \App\Models\IspSetting::get('hotspot_dns', 'mks.net');

        $logoText = $ispName ?: 'MKS';
        $logoSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="85" height="20" viewBox="0 0 85 20"><rect width="85" height="20" fill="transparent"/><text x="2" y="15" font-family="Tahoma, Arial, sans-serif" font-size="13" font-weight="900" fill="#1e293b">' . htmlspecialchars(substr($logoText, 0, 10)) . '</text></svg>';
        $defaultLogo = 'data:image/svg+xml;base64,' . base64_encode($logoSvg);
        $logo = \App\Models\IspSetting::get('company_logo') ?: $defaultLogo;
    @endphp

    <div class="sheet-container">
        @foreach ($vouchers as $v)
            @php
                $validity = $v->validity ?? '1 Hari';
                $timelimit = '';
                $datalimit = ($v->shared_users > 1) ? '👥 ' . $v->shared_users . ' Perangkat' : '';

                if (substr($validity, -1) == 'd') {
                    $validity = '   <br>MASA AKTIF : ' . substr($validity, 0, -1) . ' HARI';
                } elseif (substr($validity, -1) == 'h') {
                    $validity = 'MASA AKTIF : ' . substr($validity, 0, -1) . 'Jam';
                } elseif (stripos($validity, 'Hari') !== false) {
                    $validity = 'MASA AKTIF : ' . strtoupper($validity);
                } elseif (stripos($validity, 'Jam') !== false) {
                    $validity = 'MASA AKTIF : ' . strtoupper($validity);
                }

                $rawPrice = (int) $v->price;
                $getsprice = (string) $rawPrice;

                if ($getsprice == '3000') {
                    $color = '#666';
                } elseif ($getsprice == '1000') {
                    $color = '#FF1493';
                } elseif ($getsprice == '2000') {
                    $color = '#8B008B';
                } elseif ($getsprice == '5000') {
                    $color = '#FF4500';
                } elseif ($getsprice == '10000') {
                    $color = '#E65100';
                } elseif ($getsprice == '15000') {
                    $color = '#228B22';
                } elseif ($getsprice == '20000') {
                    $color = '#008000';
                } elseif ($getsprice == '30000') {
                    $color = '#FF00FF';
                } elseif ($getsprice == '60000') {
                    $color = '#E60C00';
                } elseif ($getsprice == '70000') {
                    $color = '#FF0000';
                } else {
                    $color = '#BA68C8';
                }

                $price = 'Rp ' . number_format($rawPrice, 0, '', ' ');
                $username = $v->code;
                $password = $v->password;
                $v_opsi = 'default';

                try {
                    $qrSrc = $qrRenderer->render($username);
                    $qrcode = '<img class="qrcode" style="height:60px;width:60px;" src="' . $qrSrc . '" alt="QR">';
                } catch (\Throwable $e) {
                    $qrcode = '';
                }
            @endphp

            <!--mks-mulai-->
            <table style="display: inline-block;border-collapse: collapse;border: 1px solid #666;margin: 2px;width: 190px;overflow:hidden;position:relative;padding: 0px;border: 1px solid #000000;background:#ffffff;page-break-inside: avoid;">
                <tbody>
                    <tr>
                        <td style="color:#666;" valign="top">
                            <table style="width:100%;">
                                <tbody>
                                    <tr>
                                        <td style="width:75px">
                                            <div style="position:relative;z-index:0;padding: 0px;float:left;">
                                                <div style="position:absolute;top:0;display:inline;margin-top:-100px;width: 0; height: 0; border-top: 230px solid transparent;border-left: 50px solid transparent;border-right:140px solid #DCDCDC;"></div>
                                            </div>
                                            <img style="margin:5px 0 0 5px;position:relative;z-index:1;" width="85" height="20" src="<?php echo $logo; ?>" alt="logo">
                                        </td>
                                        <td style="width:115px">
                                            <div style="float:right;margin-top:-6px;margin-right:0px;width:5%;text-align:right;font-size:7px;"></div>
                                            <div style="text-align:right;font-weight:bold;font-family:Tahoma;font-size:20px;padding-left:17px;color:<?php echo $color; ?>">
                                                <small style="font-size:10px;margin-left:-17px;position:absolute;"><?= explode(' ', $price)[0] ?></small><?= explode(' ', $price)[1] ?>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="color:#666;border-collapse: collapse;" valign="top">
                            <table style="width:100%;border-collapse: collapse;">
                                <tbody>
                                    <tr>
                                        <td style="width:95px" valign="top">
                                            <div style="clear:both;color:#555;margin-top:2px;margin-bottom:2.5px;">
                                                <?php if($v_opsi=='up'){ ?>
                                                <?php }else{ ?>
                                                <div style="padding:0px;border-bottom:1px solid <?php echo $color; ?>;text-align:center;font-weight:bold;font-size:10px;">VOUCHER</div>
                                                <div style="padding:0px;border-bottom:1px solid <?php echo $color; ?>;text-align:center;font-weight:bold;font-size:14px;color:#000000;font-family:monospace;"><?php echo $username; ?></div>
                                                <?php if ($password && $password !== $username): ?>
                                                    <div style="font-size:8px;color:#333;text-align:center;padding-top:1px;">Pass: <b><?php echo $password; ?></b></div>
                                                <?php endif; ?>
                                                <?php } ?>
                                            </div>
                                            <div style="text-align:center;color:#111;font-size:7px;font-weight:bold;margin:0px;padding:2.5px;line-height:1.2;">
                                                Hubungkan ke wifi {{ $hotspotName }}<br>
                                                Buka browser ketik : {{ $hotspotDns }}
                                            </div>
                                        </td>
                                        <td style="width:100px;text-align:right;" valign="top">
                                            <div style="clear:both;padding:0 2.5px;font-size:7px;font-weight:bold;color:#000000;line-height:1.2;">
                                                <?php echo $validity; ?><br> <?php echo $timelimit; ?> <br><?php echo $datalimit; ?>
                                            </div>
                                            <div style="float:right;padding:1px;text-align:right;width:70%;margin:0 5px 2px 0;"><?= $qrcode ?></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="background:<?php echo $color; ?>;color:#666;padding:0px;-webkit-print-color-adjust:exact;" valign="top" colspan="2">
                                            <div style="text-align:left;color:#fff;font-size:9px;font-weight:bold;margin:0px;padding:2.5px;">
                                                CS: <b>{{ $csPhone }}</b>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
            <!--mks-akhir-->
        @endforeach
    </div>
</body>
</html>
