<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = array(
            array('id' => 1101, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Bakı'),
            array('id' => 1102, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Ağcabədi'),
            array('id' => 1103, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Ağdam'),
            array('id' => 1104, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Ağdaş'),
            array('id' => 1105, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Ağdərə'),
            array('id' => 1106, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Ağstafa'),
            array('id' => 1107, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Ağsu'),
            array('id' => 1108, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Astara'),
            array('id' => 1109, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Balakən'),
            array('id' => 1110, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Beyləqan'),
            array('id' => 1111, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Bərdə'),
            array('id' => 1112, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Biləsuvar'),
            array('id' => 1113, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Cəbrayıl'),
            array('id' => 1114, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Cəlilabad'),
            array('id' => 1115, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Culfa'),
            array('id' => 1116, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Daşkəsən'),
            array('id' => 1117, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Dəliməmmədli'),
            array('id' => 1118, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Füzuli'),
            array('id' => 1119, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Gədəbəy'),
            array('id' => 1120, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Gəncə'),
            array('id' => 1121, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Goranboy'),
            array('id' => 1122, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Göyçay'),
            array('id' => 1123, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Göygöl'),
            array('id' => 1124, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Göytəpə'),
            array('id' => 1125, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Hacıqabul'),
            array('id' => 1126, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Horadiz'),
            array('id' => 1127, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Xaçmaz'),
            array('id' => 1128, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Xankəndi'),
            array('id' => 1129, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Xocalı'),
            array('id' => 1130, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Xocavənd'),
            array('id' => 1131, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Xırdalan'),
            array('id' => 1132, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Xızı'),
            array('id' => 1133, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Xudat'),
            array('id' => 1134, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'İmişli'),
            array('id' => 1135, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'İsmayıllı'),
            array('id' => 1136, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Kəlbəcər'),
            array('id' => 1137, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Kürdəmir'),
            array('id' => 1138, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Qax'),
            array('id' => 1139, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Qazax'),
            array('id' => 1140, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Qəbələ'),
            array('id' => 1141, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Qobustan'),
            array('id' => 1142, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Quba'),
            array('id' => 1143, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Qubadlı'),
            array('id' => 1144, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Qusar'),
            array('id' => 1145, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Laçın'),
            array('id' => 1146, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Lerik'),
            array('id' => 1147, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Lənkəran'),
            array('id' => 1148, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Liman'),
            array('id' => 1149, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Masallı'),
            array('id' => 1150, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Mingəçevir'),
            array('id' => 1151, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Naftalan'),
            array('id' => 1152, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Naxçıvan'),
            array('id' => 1153, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Neftçala'),
            array('id' => 1154, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Oğuz'),
            array('id' => 1155, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Ordubad'),
            array('id' => 1156, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Saatlı'),
            array('id' => 1157, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Sabirabad'),
            array('id' => 1158, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Salyan'),
            array('id' => 1159, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Samux'),
            array('id' => 1160, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Siyəzən'),
            array('id' => 1161, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Sumqayıt'),
            array('id' => 1162, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Şabran'),
            array('id' => 1163, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Şahbuz'),
            array('id' => 1164, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Şamaxı'),
            array('id' => 1165, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Şəki'),
            array('id' => 1166, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Şəmkir'),
            array('id' => 1167, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Şərur'),
            array('id' => 1168, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Şirvan'),
            array('id' => 1169, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Şuşa'),
            array('id' => 1170, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Tərtər'),
            array('id' => 1171, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Tovuz'),
            array('id' => 1172, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Ucar'),
            array('id' => 1173, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Yardımlı'),
            array('id' => 1174, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Yevlax'),
            array('id' => 1175, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Zaqatala'),
            array('id' => 1176, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Zəngilan'),
            array('id' => 1177, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Zərdab'),
            array('id' => 1179, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Binəqədi'),
            array('id' => 1180, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Qaradağ'),
            array('id' => 1181, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Xəzər'),
            array('id' => 1182, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Səbail'),
            array('id' => 1183, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Sabunçu'),
            array('id' => 1184, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Suraxanı'),
            array('id' => 1185, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Nərimanov'),
            array('id' => 1186, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Nəsimi'),
            array('id' => 1187, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Nizami'),
            array('id' => 1188, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Xətai'),
            array('id' => 1189, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Yasamal'),
            array('id' => 1190, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Pirallahı'),
            array('id' => 1191, 'country_id' => 11, 'parent_id' => NULL, 'name' => 'Hadrut')
        );

        foreach ($cities as $key => $city)
        {
            City::updateOrCreate(
                [
                    'id' => $city['id']
                ],
                [
                    'country_id' => $city['country_id'],
                    'parent_id' => $city['parent_id'],
                    'name' => $city['name']
                ]);
        }
    }
}
