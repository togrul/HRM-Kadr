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
            'candidate-applications.create' => 'Namizəd müraciəti yaratmaq və qəbul axınına yeni müraciət daxil etmək icazəsi verir.',
            'candidate-applications.transition' => 'Namizəd müraciətini mərhələlər arasında keçirmək və qəbul axınını irəli aparmaq icazəsi verir.',
            'candidate-applications.appoint' => 'Təsdiqlənmiş namizəd müraciəti üzrə təyinat əməliyyatını icra etmək icazəsi verir.',
            'candidate-applications.reject' => 'Namizəd müraciətini rədd etmək və rədd səbəbini qeyd etmək icazəsi verir.',
            'show-business_trips' => 'Ezamiyyət modulunu görmək, ezamiyyət qeydlərini və status axınını izləmək icazəsi verir.',
            'add-business_trips' => 'Yeni ezamiyyət qeydi yaratmaq və təsdiq axınına daxil etmək icazəsi verir.',
            'edit-business_trips' => 'Ezamiyyət qeydlərini redaktə etmək, tarix və səbəb kimi məlumatları yeniləmək icazəsi verir.',
            'delete-business_trips' => 'Ezamiyyət qeydlərini silmək və ya ləğv etmək icazəsi verir.',
            'show-vacations' => 'Məzuniyyət modulunu görmək, məzuniyyət qeydlərini və statuslarını izləmək icazəsi verir.',
            'add-vacations' => 'Yeni məzuniyyət müraciəti və ya qeydi yaratmaq icazəsi verir.',
            'edit-vacations' => 'Məzuniyyət qeydlərini redaktə etmək, tarix və səbəb məlumatlarını yeniləmək icazəsi verir.',
            'delete-vacations' => 'Məzuniyyət qeydlərini silmək və ya ləğv etmək icazəsi verir.',
            'export-orders' => 'Əmrlər üzrə siyahı, hesabat və sənəd nəticələrini ixrac etmək icazəsi verir.',
            'export-personnels' => 'Kadr siyahılarını və əməkdaş məlumatlarını ixrac etmək icazəsi verir.',
            'export-staff' => 'Ştat cədvəli məlumatlarını ixrac etmək icazəsi verir.',
            'export-candidates' => 'Namizəd siyahılarını və namizəd məlumatlarını ixrac etmək icazəsi verir.',
            'export-vacations' => 'Məzuniyyət məlumatlarını və hesabatlarını ixrac etmək icazəsi verir.',
            'export-business_trips' => 'Ezamiyyət məlumatlarını və hesabatlarını ixrac etmək icazəsi verir.',
            'update-personnels' => 'Əməkdaşların kütləvi və ya xidmət səviyyəsində məlumat yeniləmələrini icra etmək icazəsi verir.',
            'get-notification' => 'Sistem bildirişlərini almaq, bildiriş panelində görmək və istifadəçi bildiriş axınında iştirak etmək icazəsi verir.',
            'manage-notification-templates' => 'Bildiriş şablonlarını yaratmaq, redaktə etmək, test göndərişi etmək və silmək icazəsi verir.',
            'manage-notification-rules' => 'Bildiriş qaydalarını, işə salma şərtlərini və auditoriya müəyyənləşdirmə konfiqurasiyasını idarə etmək icazəsi verir.',
            'manage-notification-campaigns' => 'Əl ilə elan və bayram kampaniyalarını yaratmaq, planlaşdırmaq, təkrar göndərmək və kampaniya lövhəsini idarə etmək icazəsi verir.',
            'approve-notification-campaigns' => 'Təsdiq növbəsindəki bildiriş kampaniyalarını təsdiqləmək və ya rədd etmək icazəsi verir.',
            'confirmation-general' => 'Ümumi təsdiq axınlarında qərar vermək, təsdiq və ya rədd əməliyyatları aparmaq icazəsi verir.',
            'manage-staff' => 'Ştat cədvəli üzrə tam idarəetmə, planlama və struktur səviyyəli dəyişikliklər aparmaq icazəsi verir.',
            'add-leaves' => 'Yeni icazə və ya digər qeyri-iştirak qeydi yaratmaq icazəsi verir.',
            'show-leaves' => 'İcazələr modulunu görmək və mövcud qeydləri izləmək icazəsi verir.',
            'edit-leaves' => 'İcazə qeydlərini redaktə etmək və status/məzmun dəyişiklikləri aparmaq icazəsi verir.',
            'delete-leaves' => 'İcazə qeydlərini silmək və ya ləğv etmək icazəsi verir.',
            'export-leaves' => 'İcazə və qeyri-iştirak məlumatlarını ixrac etmək icazəsi verir.',
            'show-attendance' => 'Davamiyyət modulunun əsas ekranını açmaq və davamiyyət məlumatlarına ümumi baxış etmək icazəsi verir.',
            'show-attendance-daily-monitor' => 'Günlük monitor bölməsini görmək, bugün üzrə giriş-çıxış, gecikmə və iştirak vəziyyətini izləmək icazəsi verir.',
            'show-attendance-puantaj' => 'Puantaj cədvəlini görmək, aylıq gün-gün işlənən vaxt və statuslara baxmaq icazəsi verir.',
            'show-attendance-manager-summary' => 'Rəhbər xülasəsi bölməsini görmək, komanda üzrə aylıq davamiyyət göstəricilərini və əməkdaş xülasələrini izləmək icazəsi verir.',
            'show-attendance-manual' => 'Əl ilə giriş bölməsini görmək və davamiyyət qeydlərini əl ilə daxil etmə ekranını açmaq icazəsi verir.',
            'show-attendance-exceptions' => 'İstisnalar bölməsini görmək, çatışmayan giriş-çıxış və digər davamiyyət problemlərini izləmək icazəsi verir.',
            'show-attendance-overtime' => 'Əlavə iş panelini görmək, əlavə iş sorğularını və təsdiq vəziyyətini izləmək icazəsi verir.',
            'show-attendance-month-close' => 'Ay bağlanması bölməsini görmək və bağlanmış/bağlanmamış dövrlərə baxmaq icazəsi verir.',
            'show-attendance-history' => 'Davamiyyət audit tarixçəsini görmək, cədvəl, növbə, təyinat və qayda dəyişikliklərini tarixçə ilə izləmək icazəsi verir.',
            'manage-attendance' => 'Davamiyyət modulu üzrə ümumi idarəetmə, əməliyyat və nəzarət funksiyalarını icra etmək icazəsi verir.',
            'manage-attendance-settings' => 'Davamiyyət tənzimləmələrini, standart növbə, güzəşt qaydaları və hesablama siyasətlərini dəyişmək icazəsi verir.',
            'manage-attendance-shifts' => 'Növbələri yaratmaq, redaktə etmək, deaktiv etmək və kadrlar üzrə növbə təyinatlarını idarə etmək icazəsi verir.',
            'manage-attendance-calendars' => 'Davamiyyət iş rejimi təqvimini idarə etmək, ümumi və struktur səviyyəli iş günü/bayram dəyişikliklərini aparmaq icazəsi verir.',
            'add-attendance-manual' => 'Əl ilə davamiyyət qeydi yaratmaq və yeni girişləri sistemə daxil etmək icazəsi verir.',
            'edit-attendance-manual' => 'Əl ilə davamiyyət qeydlərini redaktə etmək və hesab parametrlərini yeniləmək icazəsi verir.',
            'approve-attendance-manual' => 'Əl ilə davamiyyət qeydlərini təsdiqləmək və ya rədd etmək icazəsi verir.',
            'approve-attendance-overtime' => 'Əlavə iş sorğularını təsdiqləmək və ya rədd etmək icazəsi verir.',
            'manage-attendance-month-close' => 'Davamiyyət dövrlərini bağlamaq, açmaq və kilidli ay nəzarətini idarə etmək icazəsi verir.',
            'edit-attendance-exceptions' => 'Davamiyyət istisnalarını redaktə etmək, həll etmək və düzəliş axınını idarə etmək icazəsi verir.',
            'export-attendance' => 'Davamiyyət, puantaj və əlaqəli hesabatları ixrac etmək icazəsi verir.',
            'show-training-needs' => 'Təlim ehtiyacı modulunu görmək, kompetensiya kataloqu, ehtiyaclar, planlar və nəticələrə baxmaq icazəsi verir.',
            'manage-training-needs' => 'Təlim ehtiyacı modulu daxilində kataloq, profil, ehtiyac, plan, sessiya və təlim nəticələrini idarə etmək icazəsi verir.',
            'review-training-needs' => 'Təlim planı təkliflərini HR səviyyəsində düzəltmək, təsdiqləmək və yoxlama axınını idarə etmək icazəsi verir.',
            'export-training-needs' => 'Təlim ehtiyacı modulu üzrə çatdırılma, rəy və analitika hesabatlarını ixrac etmək icazəsi verir.',
            'show-performance-evaluation' => 'Performans qiymətləndirməsi modulunu görmək, dövr, şablon, forma və test nəticələrinə baxmaq icazəsi verir.',
            'manage-performance-evaluation' => 'Performans qiymətləndirmə modulu daxilində dövrlər, şablonlar, formalar, ballar və test sessiyalarını idarə etmək icazəsi verir.',
            'review-performance-evaluation' => 'Açıq cavabların yoxlanılması, zəif sahə təsdiqi və qiymətləndirmə nəticələrinin audit səviyyəsində nəzərdən keçirilməsi icazəsi verir.',
            'show-reports' => 'Hesabatlar modulunu görmək, HR analitika ekranlarını, standart və müqayisəli hesabatları açmaq icazəsi verir.',
            'export-reports' => 'Hesabatlar modulu daxilində Excel, CSV və PDF/çap nəticələrini çıxarmaq icazəsi verir.',
            'show-my-hr' => 'Şəxsi kabinetə daxil olmaq, əməkdaşın öz HR xülasəsini, özünəxidmət bölmələrini və ona təyin olunmuş kontenti görmək icazəsi verir.',
            'manage-my-hr-accounts' => 'Əməkdaş üçün özünəxidmət hesab yaratmaq, açıq istifadəçi-əməkdaş bağını yeniləmək və parol təyin etmə keçidini yenidən generasiya etmək icazəsi verir.',
            'submit-self-service-leaves' => 'Əməkdaşın şəxsi kabinetdən öz adından icazə müraciəti yaratmaq və göndərmək icazəsi verir.',
            'submit-self-service-vacations' => 'Əməkdaşın şəxsi kabinetdən öz adından məzuniyyət müraciəti yaratmaq və göndərmək icazəsi verir.',
            'submit-self-service-business-trips' => 'Əməkdaşın şəxsi kabinetdən öz adından ezamiyyət müraciəti yaratmaq və göndərmək icazəsi verir.',
            'view-own-onboarding-documents' => 'Əməkdaşın özünə təyin olunmuş uyğunlaşma sənədlərini görmək və oxunma vəziyyətini izləmək icazəsi verir.',
            'acknowledge-own-onboarding-documents' => 'Əməkdaşın öz uyğunlaşma sənədləri üzrə tanışlıq təsdiqi vermək icazəsi verir.',
            'manage-onboarding-document-templates' => 'Uyğunlaşma sənəd şablonlarını yaratmaq, redaktə etmək və versiyalarını idarə etmək icazəsi verir.',
            'assign-onboarding-documents' => 'Uyğunlaşma sənədlərini əməkdaşlara təyin etmək, son tarix vermək və təyinat axınını idarə etmək icazəsi verir.',
            'review-self-service-requests' => 'Əməkdaş özünəxidmət sistemi ilə göndərilmiş icazə, məzuniyyət, ezamiyyət və düzəliş müraciətlərini nəzərdən keçirmək, təsdiqləmək və rədd etmək icazəsi verir.',
            'review-all-self-service-requests' => 'HR və administratorun bütün özünəxidmət müraciətlərini mərkəzi yoxlama növbəsindən görüb idarə etməsinə icazə verir.',
            'request-own-request-correction' => 'Əməkdaşın öz özünəxidmət müraciətləri üçün düzəliş müraciəti yaratmaq icazəsi verir.',
            'view-own-learning-content' => 'Əməkdaşın özünə təyin olunmuş öyrənmə materiallarını görmək, açmaq və tamamlanma vəziyyətini izləmək icazəsi verir.',
            'view-own-personnel-documents' => 'Əməkdaşın öz kadr kartına bağlı görünən sənədləri şəxsi kabinetdə görmək və açmaq icazəsi verir.',
            'view-own-hierarchy' => 'Əməkdaşın rəhbərini, struktur yolunu və birbaşa tabelik xəttini şəxsi kabinetdə görmək icazəsi verir.',
            'manage-employee-content-library' => 'Öyrənmə materialları kitabxanasını yaratmaq, material yükləmək və kontent növlərini idarə etmək icazəsi verir.',
            'assign-employee-content' => 'Öyrənmə materiallarını əməkdaşlara təyin etmək, son tarix vermək və təyinat axınını idarə etmək icazəsi verir.',
            'view-onboarding-library' => 'Uyğunlaşma kitabxanası modulunu görmək, şablonları və təyinat statistikasını mərkəzi paneldən izləmək icazəsi verir.',
            'view-learning-library' => 'Öyrənmə kitabxanası modulunu görmək, material kitabxanasını və təyinat statistikasını mərkəzi paneldən izləmək icazəsi verir.',
            'show-audit-logs' => 'Audit jurnalı modulunu görmək, sistem hadisələri, istifadəçi fəaliyyətləri və log detallarını izləmək icazəsi verir.',
            'show-document-compliance' => 'Sənəd uyğunluğu modulunu görmək, vaxtı bitmiş və yaxın müddətdə bitəcək əməkdaş sənədlərini izləmək icazəsi verir.',
            'show-employee-lifecycle' => 'Əməkdaş həyat dövrü modulunu görmək, uyğunlaşma, sınaq müddəti, daxili yerdəyişmə və işdən ayrılma proseslərini izləmək icazəsi verir.',
            'manage-employee-lifecycle' => 'Həyat dövrü plan şablonlarını yaratmaq, əməkdaş üçün həyat dövrü proseslərini başlatmaq və tapşırıq axınını idarə etmək icazəsi verir.',
        ];
    }

    public static function describe(string $permission): string
    {
        return static::all()[$permission] ?? static::fallbackDescription($permission);
    }

    private static function fallbackDescription(string $permission): string
    {
        return 'Bu icazə müvafiq ekran və funksiyalara giriş vermək üçün istifadə olunur.';
    }
}
