<?php

namespace App\Support\Permissions;

class PermissionDescriptionCatalog
{
    /**
     * @return array<string,string>
     */
    public static function all(): array
    {
        return [
            'show-staff' => 'Ştat cədvəli modulunu görmək, struktur üzrə mövcud ştat vahidlərini və ümumi vəziyyəti izləmək icazəsi verir.',
            'edit-staff' => 'Ştat cədvəli daxilində mövcud ştat vahidlərini və onların parametrlərini redaktə etmək icazəsi verir.',
            'access-admin' => 'Admin panelinə giriş etmək və yalnız inzibati istifadəçilər üçün nəzərdə tutulmuş idarəetmə ekranlarını açmaq icazəsi verir.',
            'add-staff' => 'Yeni ştat vahidi yaratmaq və struktur daxilində yeni ştat planı əlavə etmək icazəsi verir.',
            'delete-staff' => 'Ştat vahidlərini silmək və ya istifadədən çıxarmaq icazəsi verir.',
            'show-orders' => 'Əmrlər modulunu görmək, əmrlərin siyahısını, statusunu və detallarını izləmək icazəsi verir.',
            'add-orders' => 'Yeni əmr yaratmaq, komponent əlavə etmək və təsdiq axınına göndərmək icazəsi verir.',
            'edit-orders' => 'Mövcud əmrləri redaktə etmək, məzmununu yeniləmək və dəyişiklik tətbiq etmək icazəsi verir.',
            'delete-orders' => 'Əmrləri silmək və ya arxivləşdirmək üçün silinmə əməliyyatı aparmaq icazəsi verir.',
            'manage-order-template-sets' => 'Əmr şablon setlərini yaratmaq, redaktə etmək, silmək və strukturunu idarə etmək icazəsi verir.',
            'manage-order-template-metadata' => 'Əmr şablonlarının metadata, field, mapping, UI config və placeholder uyğunluğunu idarə etmək icazəsi verir.',
            'manage-order-template-versions' => 'Əmr şablonu versiyalarını draft, publish, rollback və aktiv versiya səviyyəsində idarə etmək icazəsi verir.',
            'show-personnels' => 'Kadrlar modulunu görmək, əməkdaşların siyahısını və əsas məlumatlarını izləmək icazəsi verir.',
            'add-personnels' => 'Yeni əməkdaş kartı yaratmaq və kadr məlumatlarını sistemə daxil etmək icazəsi verir.',
            'edit-personnels' => 'Əməkdaş kartlarını redaktə etmək, məlumatları yeniləmək və dəyişiklikləri tətbiq etmək icazəsi verir.',
            'delete-personnels' => 'Əməkdaş kartlarını silmək və ya arxiv statusuna keçirmək icazəsi verir.',
            'access-settings' => 'Sistem tənzimləmələri, xidmət modulu və inzibati sazlamalara giriş etmək icazəsi verir.',
            'show-candidates' => 'Namizədlər modulunu görmək, namizəd siyahısını və statuslarını izləmək icazəsi verir.',
            'add-candidates' => 'Yeni namizəd yaratmaq və ilkin qəbul məlumatlarını sistemə daxil etmək icazəsi verir.',
            'edit-candidates' => 'Namizəd məlumatlarını redaktə etmək, status dəyişmək və sənədləri yeniləmək icazəsi verir.',
            'delete-candidates' => 'Namizəd qeydlərini silmək və ya arxivləşdirmək icazəsi verir.',
            'show-business_trips' => 'Ezamiyyət modulunu görmək, ezamiyyət qeydlərini və status axınını izləmək icazəsi verir.',
            'add-business_trips' => 'Yeni ezamiyyət qeydi yaratmaq və təsdiq axınına daxil etmək icazəsi verir.',
            'edit-business_trips' => 'Ezamiyyət qeydlərini redaktə etmək, tarix və səbəb kimi məlumatları yeniləmək icazəsi verir.',
            'delete-business_trips' => 'Ezamiyyət qeydlərini silmək və ya ləğv etmək icazəsi verir.',
            'show-vacations' => 'Məzuniyyət modulunu görmək, məzuniyyət qeydlərini və statuslarını izləmək icazəsi verir.',
            'add-vacations' => 'Yeni məzuniyyət müraciəti və ya qeydi yaratmaq icazəsi verir.',
            'edit-vacations' => 'Məzuniyyət qeydlərini redaktə etmək, tarix və səbəb məlumatlarını yeniləmək icazəsi verir.',
            'delete-vacations' => 'Məzuniyyət qeydlərini silmək və ya ləğv etmək icazəsi verir.',
            'export-orders' => 'Əmrlər üzrə siyahı, hesabat və sənəd nəticələrini export etmək icazəsi verir.',
            'export-personnels' => 'Kadr siyahılarını və əməkdaş məlumatlarını export etmək icazəsi verir.',
            'export-staff' => 'Ştat cədvəli məlumatlarını export etmək icazəsi verir.',
            'export-candidates' => 'Namizəd siyahılarını və namizəd məlumatlarını export etmək icazəsi verir.',
            'export-vacations' => 'Məzuniyyət məlumatlarını və hesabatlarını export etmək icazəsi verir.',
            'export-business_trips' => 'Ezamiyyət məlumatlarını və hesabatlarını export etmək icazəsi verir.',
            'update-personnels' => 'Əməkdaşların kütləvi və ya xidmət səviyyəsində məlumat yeniləmələrini icra etmək icazəsi verir.',
            'get-notification' => 'Sistem bildirişlərini almaq, bildiriş panelində görmək və istifadəçi bildiriş axınında iştirak etmək icazəsi verir.',
            'confirmation-general' => 'Ümumi təsdiq axınlarında qərar vermək, təsdiq və ya rədd əməliyyatları aparmaq icazəsi verir.',
            'manage-staff' => 'Ştat cədvəli üzrə tam idarəetmə, planlama və struktur səviyyəli dəyişikliklər aparmaq icazəsi verir.',
            'add-leaves' => 'Yeni icazə və ya digər qeyri-iştirak qeydi yaratmaq icazəsi verir.',
            'show-leaves' => 'İcazələr modulunu görmək və mövcud qeydləri izləmək icazəsi verir.',
            'edit-leaves' => 'İcazə qeydlərini redaktə etmək və status/məzmun dəyişiklikləri aparmaq icazəsi verir.',
            'delete-leaves' => 'İcazə qeydlərini silmək və ya ləğv etmək icazəsi verir.',
            'export-leaves' => 'İcazə və qeyri-iştirak məlumatlarını export etmək icazəsi verir.',
            'show-attendance' => 'Davamiyyət modulunun əsas ekranını açmaq və davamiyyət məlumatlarına ümumi baxış etmək icazəsi verir.',
            'show-attendance-daily-monitor' => 'Günlük monitor bölməsini görmək, bugün üzrə giriş-çıxış, gecikmə və iştirak vəziyyətini izləmək icazəsi verir.',
            'show-attendance-puantaj' => 'Puantaj cədvəlini görmək, aylıq gün-gün işlənən vaxt və statuslara baxmaq icazəsi verir.',
            'show-attendance-manual' => 'Manual giriş bölməsini görmək və attendance qeydlərini əl ilə daxil etmə ekranını açmaq icazəsi verir.',
            'show-attendance-exceptions' => 'İstisnalar inbox-unu görmək, missing in/out və digər attendance problemlərini izləmək icazəsi verir.',
            'show-attendance-overtime' => 'Əlavə iş panelini görmək, overtime sorğularını və approval vəziyyətini izləmək icazəsi verir.',
            'show-attendance-month-close' => 'Ay bağlanması bölməsini görmək və bağlanmış/bağlanmamış dövrlərə baxmaq icazəsi verir.',
            'manage-attendance' => 'Davamiyyət modulu üzrə ümumi idarəetmə, əməliyyat və nəzarət funksiyalarını icra etmək icazəsi verir.',
            'manage-attendance-settings' => 'Attendance tənzimləmələrini, default shift, grace qaydaları və hesablama siyasətlərini dəyişmək icazəsi verir.',
            'manage-attendance-shifts' => 'Növbələri yaratmaq, redaktə etmək, deaktiv etmək və kadrlar üzrə növbə təyinatlarını idarə etmək icazəsi verir.',
            'manage-attendance-calendars' => 'Attendance iş rejimi təqvimini idarə etmək, ümumi və struktur səviyyəli iş günü/bayram override-larını dəyişmək icazəsi verir.',
            'add-attendance-manual' => 'Manual attendance qeydi yaratmaq və yeni girişləri sistemə daxil etmək icazəsi verir.',
            'edit-attendance-manual' => 'Manual attendance qeydlərini redaktə etmək və hesab parametrlərini yeniləmək icazəsi verir.',
            'approve-attendance-manual' => 'Manual attendance qeydlərini təsdiqləmək və ya rədd etmək icazəsi verir.',
            'approve-attendance-overtime' => 'Əlavə iş sorğularını təsdiqləmək və ya rədd etmək icazəsi verir.',
            'manage-attendance-month-close' => 'Attendance dövrlərini bağlamaq, açmaq və locked month nəzarətini idarə etmək icazəsi verir.',
            'edit-attendance-exceptions' => 'Attendance istisnalarını redaktə etmək, resolve etmək və correction axınını idarə etmək icazəsi verir.',
            'export-attendance' => 'Attendance, puantaj və əlaqəli hesabatları export etmək icazəsi verir.',
        ];
    }

    public static function describe(string $permission): string
    {
        return static::all()[$permission] ?? static::fallbackDescription($permission);
    }

    private static function fallbackDescription(string $permission): string
    {
        $label = str_replace(['_', '-'], ' ', $permission);

        return "Bu icazə `{$label}` əməliyyatı üzrə müvafiq ekran və funksiyalara giriş vermək üçün istifadə olunur.";
    }
}
