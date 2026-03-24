# Zenei Webshop – Online Értékesítő Webalkalmazás

Ez a projekt egy **zenéket és zenei tartalmakat forgalmazó webshop** számára készült webalkalmazás, amely lehetővé teszi a felhasználók számára az online zeneböngészést és vásárlást, valamint külön adminisztrációs felületet biztosít az oldal üzemeltetőinek a zenék, rendelések és felhasználók kezelésére.

A rendszer **Laravel (backend API)** és **Vue.js (frontend)** technológiákra épül.

---

## Fő funkciók

### Felhasználói (User) funkciók

* Regisztráció és bejelentkezés
* Zenék böngészése kategóriák (pl. műfajok) szerint
* Zenék keresése és szűrése (**ár, műfaj, előadó**)
* Zene részleteinek megtekintése (**leírás, ár, előadó, hossz**)
* Zenék kosárba helyezése
* Kosár tartalmának módosítása (**mennyiség, törlés**)
* Online rendelés leadása
* Zeneértékelés leadása vásárlás után (**szöveges vélemény**)

---

### Admin funkciók

* Admin felület bejelentkezés után
* Új zenék feltöltése
* Zenék szerkesztése és törlése
* Kategóriák (**műfajok**) kezelése
* Tartalomkezelés
* Felhasználók kezelése

---

## Rendelési folyamat működése

* A felhasználó zenéket ad a kosárhoz
* A rendszer ellenőrzi az elérhetőséget

A rendelés leadásakor a rendszer:

* elmenti a rendelés adatait
* biztosítja a hozzáférést a megvásárolt zenékhez

*Egy zene csak elérhető állapot esetén vásárolható meg.*

---

## Értékelési rendszer

* Az értékelések a zene adatlapján jelennek meg
* Az értékelések tartalmazzák:

  * **szöveges véleményt**

---

## Technológia

* **Backend:** Laravel (REST API)
* **Frontend:** Vue.js
* **Adatbázis:** MySQL
* **Hitelesítés:** Laravel Auth / Sanctum
* **Időzített feladatok:** Laravel Scheduler
* **Email küldés:** Laravel Mail

---

## Projekt célja

A projekt célja egy **modern, átlátható és könnyen használható zenei webshop** létrehozása, amely lehetővé teszi a gyors online vásárlást, egyszerűsíti a rendeléskezelést, és hatékony eszközt biztosít az adminisztrátorok számára a mindennapi működéshez.
