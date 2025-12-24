## Labor pozisyon/struktur seçim planı

Hedef: LaborActivity kayıtlarında geçmiş işler serbest metin kalsın; güncel iş eklendiğinde seçime göre (liste vs manuel) hem personnel.position_id/structure_id güncellensin hem labor satırı metin olarak doğru görünsün. Bozulmadan uygulanabilir adımlar:

### Seçim modu
- UI’ye “liste” / “manuel” seçimi (checkbox/radio) ekle.
- Liste modu: positions dropdown + structure tree zorunlu olur.
- Manuel modu: serbest metin (position, structure) alanları aktif kalır; ID güncellemesi yapılmaz.

### Liste modu akışı
- Kullanıcı positions dropdown’dan seçer, structure tree’den seçer.
- Labor kaydına metin olarak `"{structure_name} - {position_name}"` yazılır (labor tablosu metin tutmaya devam eder).
- Personele: position_id ve structure_id güncellenir (sadece current/is_current satır için).
- Labor satırı payload’ında ayrıca position_id/structure_id saklanabilir ama DB’ye metin yazılacak; ID’ler Personnel tablosunda tutulur.

### Manuel modu akışı
- Kullanıcı serbest metin yazar; labor satırı olduğu gibi metin olarak kaydedilir.
- Personnel.position_id / structure_id güncellenmez.

### Persist adımları (create/update)
- LaborActivityForm: seçilen moda göre payload’ı işaretle (örn. `mode => 'list' | 'manual'`).
- Persistence service: current ve mode==list olan satırı bul, ID’leri Personnel’e yaz; labor satırında sadece metin alanlarını sakla.
- Geçmiş satırlar için hiçbir ID zorlaması yapılmaz.

### UI değişiklikleri
- Labor ekleme formuna “Listeden seç / Manuel gir” toggle.
- Liste seçilince: `<x-ui.select-dropdown>` (positions) + tree structure picker açılır.
- Manuel seçilince: mevcut text input’lar aktif kalır.

### Notlar
- VMİE etiketi yalnızca current işte geçerli olduğundan, ID güncellemesi de sadece current/list modunda yapılacak.
- Seçili position/structure isimlerini form state’inde saklayarak labor satırına metin olarak yazmak yeterli; ek DB kolonuna gerek yok.
