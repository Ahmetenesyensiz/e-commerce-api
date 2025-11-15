# E-Ticaret API Case Study

Bu proje, **PHP** 8.4 ve Laravel 12 kullanılarak geliştirilmiş, PostgreSQL altyapılı RESTful bir E-Ticaret **API**'sidir.

## 📋 İçindekiler

- [Kurulum Adımları](#kurulum-adımları)
- [Veritabanı Kurulumu](#veritabanı-kurulumu)
- [Test Kullanıcıları](#test-kullanıcıları)
- [**API** Endpoint Listesi](#api-endpoint-listesi)
- [Örnek İstek ve Yanıt](#örnek-istek-ve-yanıt)

## 🛠️ Kurulum Adımları

Projeyi yerel ortamınızda çalıştırmak için aşağıdaki adımları izleyin:

1. **Projeyi İndirin:**

    ```bash
    git clone [GITHUB_REPO_LINKINIZ]
    cd e-commerce-api
    ```

2. **Bağımlılıkları Yükleyin:**

    ```bash
    composer install
    ```

3. **Çevre Değişkenlerini Ayarlayın:**
   `.env.example` dosyasının adını `.env` olarak değiştirin.

4. **Application Key Oluşturun:**

    ```bash
    php artisan key:generate
    ```

## 📚 Veritabanı Kurulumu

Bu proje PostgreSQL kullanmaktadır.

## PostgreSQL'de `e_commerce_api` adında boş bir veritabanı oluşturun.

2. `.env` dosyasında veritabanı ayarlarını yapın:

```env DB_CONNECTION=pgsql DB_HOST=**127**.0.0.1 DB_PORT=**5432** DB_DATABASE=e_commerce_api DB_USERNAME=postgres DB_PASSWORD=sifreniz ```

## Otomatik Kurulum ve Sample Data:

```bash php artisan migrate:fresh --seed ```

(Alternatif olarak kök dizindeki `database_dump.sql` dosyasını veritabanınıza import edebilirsiniz.)

## Sunucuyu Başlatın:

```bash php artisan serve ```

## 👤 Test Kullanıcıları

| Rol   | Email                                   | Şifre    |
| ----- | --------------------------------------- | -------- |
| Admin | [admin@test.com](mailto:[admin@test.com](mailto:admin@test.com)) | admin123 |
| User  | [user@test.com](mailto:[user@test.com](mailto:user@test.com))   | user123  |

- **Admin Yetkileri:** Kategori ve Ürün ekleme/silme/güncelleme, Sipariş durumu değiştirme.
- **User Yetkileri:** Sepet işlemleri, Sipariş verme, Profil görüntüleme.

## 🔗 API Endpoint Listesi

Detaylı dokümantasyon için Swagger arayüzünü kullanabilirsiniz: [http://**127**.0.0.1:**8000**/api/documentation](http://**127**.0.0.1:**8000**/api/documentation)

### Auth

- `**POST** /api/register` - Kayıt Ol
- `**POST** /api/login` - Giriş Yap (Token döner)
- `**GET** /api/profile` - Profil Görüntüle (Bearer Token gerekli)

### Ürünler & Kategoriler

- `**GET** /api/products` - Ürün Listesi (Filtreleme: `?search=abc&min_price=10`)
- `**GET** /api/categories` - Kategori Listesi
- `**POST** /api/products` - Ürün Ekle (Admin)

### Sepet & Sipariş

- `**POST** /api/cart/add` - Sepete Ekle
- `**GET** /api/cart` - Sepeti Gör
- `**POST** /api/orders` - Sipariş Ver

## 🗒 Örnek İstek ve Yanıt

### Giriş Yapma (Login)

**Request:**

```http **POST** /api/login Content-Type: application/json

{
    *email*: *[admin@test.com](mailto:admin@test.com)*,
    *password*: *admin123*
}
```

**Response (**200** OK):**

```json
{
    *success*: true,
    *message*: *Giriş başarılı*,
    *data*: {
    *user*: {
    *id*: 1,
    *name*: *Admin User*,
    *email*: *[admin@test.com](mailto:admin@test.com)*,
    *role*: *admin*
    },
    *token*: *1|XyZ...*
    }
}
```

```bash ```