<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = [
            ['id' => 1101, 'country_id' => 11, 'parent_id' => null, 'name' => 'Bakı'],
            ['id' => 1102, 'country_id' => 11, 'parent_id' => null, 'name' => 'Ağcabədi'],
            ['id' => 1103, 'country_id' => 11, 'parent_id' => null, 'name' => 'Ağdam'],
            ['id' => 1104, 'country_id' => 11, 'parent_id' => null, 'name' => 'Ağdaş'],
            ['id' => 1105, 'country_id' => 11, 'parent_id' => null, 'name' => 'Ağdərə'],
            ['id' => 1106, 'country_id' => 11, 'parent_id' => null, 'name' => 'Ağstafa'],
            ['id' => 1107, 'country_id' => 11, 'parent_id' => null, 'name' => 'Ağsu'],
            ['id' => 1108, 'country_id' => 11, 'parent_id' => null, 'name' => 'Astara'],
            ['id' => 1109, 'country_id' => 11, 'parent_id' => null, 'name' => 'Balakən'],
            ['id' => 1110, 'country_id' => 11, 'parent_id' => null, 'name' => 'Beyləqan'],
            ['id' => 1111, 'country_id' => 11, 'parent_id' => null, 'name' => 'Bərdə'],
            ['id' => 1112, 'country_id' => 11, 'parent_id' => null, 'name' => 'Biləsuvar'],
            ['id' => 1113, 'country_id' => 11, 'parent_id' => null, 'name' => 'Cəbrayıl'],
            ['id' => 1114, 'country_id' => 11, 'parent_id' => null, 'name' => 'Cəlilabad'],
            ['id' => 1115, 'country_id' => 11, 'parent_id' => null, 'name' => 'Culfa'],
            ['id' => 1116, 'country_id' => 11, 'parent_id' => null, 'name' => 'Daşkəsən'],
            ['id' => 1117, 'country_id' => 11, 'parent_id' => null, 'name' => 'Dəliməmmədli'],
            ['id' => 1118, 'country_id' => 11, 'parent_id' => null, 'name' => 'Füzuli'],
            ['id' => 1119, 'country_id' => 11, 'parent_id' => null, 'name' => 'Gədəbəy'],
            ['id' => 1120, 'country_id' => 11, 'parent_id' => null, 'name' => 'Gəncə'],
            ['id' => 1121, 'country_id' => 11, 'parent_id' => null, 'name' => 'Goranboy'],
            ['id' => 1122, 'country_id' => 11, 'parent_id' => null, 'name' => 'Göyçay'],
            ['id' => 1123, 'country_id' => 11, 'parent_id' => null, 'name' => 'Göygöl'],
            ['id' => 1124, 'country_id' => 11, 'parent_id' => null, 'name' => 'Göytəpə'],
            ['id' => 1125, 'country_id' => 11, 'parent_id' => null, 'name' => 'Hacıqabul'],
            ['id' => 1126, 'country_id' => 11, 'parent_id' => null, 'name' => 'Horadiz'],
            ['id' => 1127, 'country_id' => 11, 'parent_id' => null, 'name' => 'Xaçmaz'],
            ['id' => 1128, 'country_id' => 11, 'parent_id' => null, 'name' => 'Xankəndi'],
            ['id' => 1129, 'country_id' => 11, 'parent_id' => null, 'name' => 'Xocalı'],
            ['id' => 1130, 'country_id' => 11, 'parent_id' => null, 'name' => 'Xocavənd'],
            ['id' => 1131, 'country_id' => 11, 'parent_id' => null, 'name' => 'Xırdalan'],
            ['id' => 1132, 'country_id' => 11, 'parent_id' => null, 'name' => 'Xızı'],
            ['id' => 1133, 'country_id' => 11, 'parent_id' => null, 'name' => 'Xudat'],
            ['id' => 1134, 'country_id' => 11, 'parent_id' => null, 'name' => 'İmişli'],
            ['id' => 1135, 'country_id' => 11, 'parent_id' => null, 'name' => 'İsmayıllı'],
            ['id' => 1136, 'country_id' => 11, 'parent_id' => null, 'name' => 'Kəlbəcər'],
            ['id' => 1137, 'country_id' => 11, 'parent_id' => null, 'name' => 'Kürdəmir'],
            ['id' => 1138, 'country_id' => 11, 'parent_id' => null, 'name' => 'Qax'],
            ['id' => 1139, 'country_id' => 11, 'parent_id' => null, 'name' => 'Qazax'],
            ['id' => 1140, 'country_id' => 11, 'parent_id' => null, 'name' => 'Qəbələ'],
            ['id' => 1141, 'country_id' => 11, 'parent_id' => null, 'name' => 'Qobustan'],
            ['id' => 1142, 'country_id' => 11, 'parent_id' => null, 'name' => 'Quba'],
            ['id' => 1143, 'country_id' => 11, 'parent_id' => null, 'name' => 'Qubadlı'],
            ['id' => 1144, 'country_id' => 11, 'parent_id' => null, 'name' => 'Qusar'],
            ['id' => 1145, 'country_id' => 11, 'parent_id' => null, 'name' => 'Laçın'],
            ['id' => 1146, 'country_id' => 11, 'parent_id' => null, 'name' => 'Lerik'],
            ['id' => 1147, 'country_id' => 11, 'parent_id' => null, 'name' => 'Lənkəran'],
            ['id' => 1148, 'country_id' => 11, 'parent_id' => null, 'name' => 'Liman'],
            ['id' => 1149, 'country_id' => 11, 'parent_id' => null, 'name' => 'Masallı'],
            ['id' => 1150, 'country_id' => 11, 'parent_id' => null, 'name' => 'Mingəçevir'],
            ['id' => 1151, 'country_id' => 11, 'parent_id' => null, 'name' => 'Naftalan'],
            ['id' => 1152, 'country_id' => 11, 'parent_id' => null, 'name' => 'Naxçıvan'],
            ['id' => 1153, 'country_id' => 11, 'parent_id' => null, 'name' => 'Neftçala'],
            ['id' => 1154, 'country_id' => 11, 'parent_id' => null, 'name' => 'Oğuz'],
            ['id' => 1155, 'country_id' => 11, 'parent_id' => null, 'name' => 'Ordubad'],
            ['id' => 1156, 'country_id' => 11, 'parent_id' => null, 'name' => 'Saatlı'],
            ['id' => 1157, 'country_id' => 11, 'parent_id' => null, 'name' => 'Sabirabad'],
            ['id' => 1158, 'country_id' => 11, 'parent_id' => null, 'name' => 'Salyan'],
            ['id' => 1159, 'country_id' => 11, 'parent_id' => null, 'name' => 'Samux'],
            ['id' => 1160, 'country_id' => 11, 'parent_id' => null, 'name' => 'Siyəzən'],
            ['id' => 1161, 'country_id' => 11, 'parent_id' => null, 'name' => 'Sumqayıt'],
            ['id' => 1162, 'country_id' => 11, 'parent_id' => null, 'name' => 'Şabran'],
            ['id' => 1163, 'country_id' => 11, 'parent_id' => null, 'name' => 'Şahbuz'],
            ['id' => 1164, 'country_id' => 11, 'parent_id' => null, 'name' => 'Şamaxı'],
            ['id' => 1165, 'country_id' => 11, 'parent_id' => null, 'name' => 'Şəki'],
            ['id' => 1166, 'country_id' => 11, 'parent_id' => null, 'name' => 'Şəmkir'],
            ['id' => 1167, 'country_id' => 11, 'parent_id' => null, 'name' => 'Şərur'],
            ['id' => 1168, 'country_id' => 11, 'parent_id' => null, 'name' => 'Şirvan'],
            ['id' => 1169, 'country_id' => 11, 'parent_id' => null, 'name' => 'Şuşa'],
            ['id' => 1170, 'country_id' => 11, 'parent_id' => null, 'name' => 'Tərtər'],
            ['id' => 1171, 'country_id' => 11, 'parent_id' => null, 'name' => 'Tovuz'],
            ['id' => 1172, 'country_id' => 11, 'parent_id' => null, 'name' => 'Ucar'],
            ['id' => 1173, 'country_id' => 11, 'parent_id' => null, 'name' => 'Yardımlı'],
            ['id' => 1174, 'country_id' => 11, 'parent_id' => null, 'name' => 'Yevlax'],
            ['id' => 1175, 'country_id' => 11, 'parent_id' => null, 'name' => 'Zaqatala'],
            ['id' => 1176, 'country_id' => 11, 'parent_id' => null, 'name' => 'Zəngilan'],
            ['id' => 1177, 'country_id' => 11, 'parent_id' => null, 'name' => 'Zərdab'],
            ['id' => 1179, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Binəqədi'],
            ['id' => 1180, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Qaradağ'],
            ['id' => 1181, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Xəzər'],
            ['id' => 1182, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Səbail'],
            ['id' => 1183, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Sabunçu'],
            ['id' => 1184, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Suraxanı'],
            ['id' => 1185, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Nərimanov'],
            ['id' => 1186, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Nəsimi'],
            ['id' => 1187, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Nizami'],
            ['id' => 1188, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Xətai'],
            ['id' => 1189, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Yasamal'],
            ['id' => 1190, 'country_id' => 11, 'parent_id' => 1101, 'name' => 'Pirallahı'],
            ['id' => 1191, 'country_id' => 11, 'parent_id' => null, 'name' => 'Hadrut'],
        ];

        foreach ($cities as $key => $city) {
            City::updateOrCreate(
                [
                    'id' => $city['id'],
                ],
                [
                    'country_id' => $city['country_id'],
                    'parent_id' => $city['parent_id'],
                    'name' => $city['name'],
                ]);
        }
    }
}
