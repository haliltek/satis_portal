<table id="datatabsle" class="table table-bordered dt-responsive " style="border-collapse: collapse; border-spacing: 0; width: 100%;">
    <tbody>
        <tr>
            <td width="60%" rowspan="1"></td>
            <td width="15%" style="text-align: right;"></td>
            <td width="15%" style="text-align: right;"><?php echo $tl_toplam = $teklif["tltutar"]; ?> TL </td>
        </tr>
        <tr>
            <td width="60%" rowspan="1" style="font-size: 13px"><?php echo $teklif["kurtarih"]; ?> tarihli TCMB EURO Kuru üzerinden hesaplanmıştır. Güncel Kur: €<?php echo $eurokur; ?> </td>
            <td width="15%" style="text-align: right;"></td>
            <td width="15%" style="text-align: right;"><?php echo $eurotutar = $teklif["eurotutar"];
                                                        echo ' €<br>';
                                                        echo number_format(($eurotutar * $eurokur), 2, ',', '.');
                                                        $eurofiyatm = $eurotutar * $eurokur;
                                                        ?> ₺</td>
        </tr>
        <tr>
            <td width="60%" rowspan="1" style="font-size: 13px"><?php echo $teklif["kurtarih"]; ?> tarihli TCMB USD Kuru üzerinden hesaplanmıştır. Güncel Kur: $<?php echo $dolarkur; ?> </td>
            <td width="15%" style="text-align: right;"></td>
            <td width="15%" style="text-align: right;"><?php echo $dolartutarims = $teklif["dolartutar"];
                                                        echo " $ ";
                                                        echo "<br>";
                                                        echo number_format(($dolartutarims * $dolarkur), 2, ',', '.');
                                                        echo " ₺";
                                                        $dolarfiyat = $dolartutarims * $dolarkur; ?> </td>
        </tr>
        <hr>
        <tr>
            <td width="60%" rowspan="1"></td>
            <td width="15%" style="text-align: right;"><b> TOPLAM</b></td>
            <td width="15%" style="text-align: right;"><b><?php echo $toplami = number_format(($tl_toplam + $dolarfiyat + $eurofiyatm), 2, ',', '.');
                                                            $he = $tl_toplam + $dolarfiyat + $eurofiyatm ?> TL</b> </td>
        </tr>
        <tr>
            <td width="60%" rowspan="1"></td>
            <td width="15%" style="text-align: right;"><b> KDV</b></td>
            <td width="15%" style="text-align: right;"><b><?php $kdvtop =  ($he * 20) / 100;
                                                            echo number_format($kdvtop, 2, ',', '.'); ?> TL</b> </td>
        </tr>
        <tr>
            <td width="60%" rowspan="1"></td>
            <td width="15%" style="text-align: right;"><b>GENEL TOPLAM</b></td>
            <td width="15%" style="text-align: right;"><b><?php $gentop =  $he + $kdvtop;
                                                            echo  number_format($gentop, 2, ',', '.'); ?> TL</b> </td>
        </tr>
    </tbody>
</table>