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
            'manage-notification-templates' => 'Bildiriş şablonlarını yaratmaq, redaktə etmək, test göndərişi etmək və silmək icazəsi verir.',
            'manage-notification-rules' => 'Bildiriş qaydalarını, trigger-ləri və auditoriya resolver konfiqurasiyasını idarə etmək icazəsi verir.',
            'manage-notification-campaigns' => 'Manual elan və bayram kampaniyalarını yaratmaq, schedule etmək, retry/resend etmək və kampaniya board-u idarə etmək icazəsi verir.',
            'approve-notification-campaigns' => 'Təsdiq növbəsindəki bildiriş kampaniyalarını təsdiqləmək və ya rədd etmək icazəsi verir.',
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
            'show-attendance-manager-summary' => 'Rəhbər xülasəsi bölməsini görmək, komanda üzrə aylıq attendance KPI və əməkdaş xülasələrini izləmək icazəsi verir.',
            'show-attendance-manual' => 'Manual giriş bölməsini görmək və attendance qeydlərini əl ilə daxil etmə ekranını açmaq icazəsi verir.',
            'show-attendance-exceptions' => 'İstisnalar inbox-unu görmək, missing in/out və digər attendance problemlərini izləmək icazəsi verir.',
            'show-attendance-overtime' => 'Əlavə iş panelini görmək, overtime sorğularını və approval vəziyyətini izləmək icazəsi verir.',
            'show-attendance-month-close' => 'Ay bağlanması bölməsini görmək və bağlanmış/bağlanmamış dövrlərə baxmaq icazəsi verir.',
            'show-attendance-history' => 'Attendance audit tarixçəsini görmək, cədvəl, növbə, təyinat və qayda dəyişikliklərini tarixçə ilə izləmək icazəsi verir.',
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
            'show-training-needs' => 'Təlim ehtiyacı modulunu görmək, kompetensiya kataloqu, ehtiyaclar, planlar və nəticələrə baxmaq icazəsi verir.',
            'manage-training-needs' => 'Təlim ehtiyacı modulu daxilində kataloq, profil, ehtiyac, plan, sessiya və təlim nəticələrini idarə etmək icazəsi verir.',
            'review-training-needs' => 'Təlim planı təkliflərini HR səviyyəsində düzəltmək, təsdiqləmək və review axınını idarə etmək icazəsi verir.',
            'export-training-needs' => 'Təlim ehtiyacı modulu üzrə delivery, feedback və analitika hesabatlarını export etmək icazəsi verir.',
            'show-performance-evaluation' => 'Performans qiymətləndirməsi modulunu görmək, dövr, şablon, forma və test nəticələrinə baxmaq icazəsi verir.',
            'manage-performance-evaluation' => 'Performans qiymətləndirmə modulu daxilində dövrlər, şablonlar, formalar, ballar və test sessiyalarını idarə etmək icazəsi verir.',
            'review-performance-evaluation' => 'Açıq cavabların review edilməsi, weak-area təsdiqi və qiymətləndirmə nəticələrinin audit səviyyəsində nəzərdən keçirilməsi icazəsi verir.',
            'show-reports' => 'Hesabatlar modulunu görmək, HR analitika ekranlarını, standart və müqayisəli hesabatları açmaq icazəsi verir.',
            'export-reports' => 'Hesabatlar modulu daxilində Excel, CSV və PDF/çap nəticələrini çıxarmaq icazəsi verir.',
            'show-my-hr' => 'Şəxsi kabinetə daxil olmaq, əməkdaşın öz HR xülasəsini, self-service bölmələrini və ona təyin olunmuş kontenti görmək icazəsi verir.',
            'manage-my-hr-accounts' => 'Əməkdaş üçün self-service hesab yaratmaq, explicit user-personnel bağını yeniləmək və set-password keçidini yenidən generasiya etmək icazəsi verir.',
            'submit-self-service-leaves' => 'Əməkdaşın şəxsi kabinetdən öz adından icazə müraciəti yaratmaq və göndərmək icazəsi verir.',
            'submit-self-service-vacations' => 'Əməkdaşın şəxsi kabinetdən öz adından məzuniyyət müraciəti yaratmaq və göndərmək icazəsi verir.',
            'submit-self-service-business-trips' => 'Əməkdaşın şəxsi kabinetdən öz adından ezamiyyət müraciəti yaratmaq və göndərmək icazəsi verir.',
            'view-own-onboarding-documents' => 'Əməkdaşın özünə təyin olunmuş onboarding sənədlərini görmək və oxunma vəziyyətini izləmək icazəsi verir.',
            'acknowledge-own-onboarding-documents' => 'Əməkdaşın öz onboarding sənədləri üzrə tanışlıq təsdiqi vermək icazəsi verir.',
            'manage-onboarding-document-templates' => 'Onboarding sənəd şablonlarını yaratmaq, redaktə etmək və versiyalarını idarə etmək icazəsi verir.',
            'assign-onboarding-documents' => 'Onboarding sənədlərini əməkdaşlara təyin etmək, son tarix vermək və assignment axınını idarə etmək icazəsi verir.',
            'review-self-service-requests' => 'Employee self-service ilə göndərilmiş icazə, məzuniyyət, ezamiyyət və düzəliş müraciətlərini nəzərdən keçirmək, təsdiqləmək və rədd etmək icazəsi verir.',
            'review-all-self-service-requests' => 'HR və administratorun bütün self-service müraciətləri mərkəzi review queue-dan görüb idarə etməsinə icazə verir.',
            'request-own-request-correction' => 'Əməkdaşın öz self-service request-ləri üçün düzəliş müraciəti yaratmaq icazəsi verir.',
            'view-own-learning-content' => 'Əməkdaşın özünə təyin olunmuş öyrənmə materiallarını görmək, açmaq və completion vəziyyətini izləmək icazəsi verir.',
            'view-own-personnel-documents' => 'Əməkdaşın öz kadr kartına bağlı görünən sənədləri şəxsi kabinetdə görmək və açmaq icazəsi verir.',
            'view-own-hierarchy' => 'Əməkdaşın rəhbərini, struktur yolunu və birbaşa tabelik xəttini şəxsi kabinetdə görmək icazəsi verir.',
            'manage-employee-content-library' => 'Employee learning content kitabxanasını yaratmaq, material yükləmək və kontent növlərini idarə etmək icazəsi verir.',
            'assign-employee-content' => 'Öyrənmə materiallarını əməkdaşlara təyin etmək, son tarix vermək və assignment axınını idarə etmək icazəsi verir.',
            'view-onboarding-library' => 'Onboarding kitabxanası modulunu görmək, şablonları və assignment statistikasını mərkəzi paneldən izləmək icazəsi verir.',
            'view-learning-library' => 'Öyrənmə kitabxanası modulunu görmək, material kitabxanasını və assignment statistikasını mərkəzi paneldən izləmək icazəsi verir.',
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
