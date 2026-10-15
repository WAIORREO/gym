// @ts-nocheck
import { gymCopy } from './gym-copy.js';
import { writable, derived } from 'svelte/store';

// Default language: Arabic ('ar') or French ('fr')
const initialLang = typeof window !== 'undefined' 
    ? (localStorage.getItem('laragym_lang') || 'ar') 
    : 'ar';

export const currentLang = writable(initialLang);

// Direction store: 'rtl' for Arabic, 'ltr' for French
export const dir = derived(currentLang, ($lang) => ($lang === 'ar' ? 'rtl' : 'ltr'));

export const translations = {
    ar: {
        // App
        app_name: 'لارا جيم',
        app_tagline: 'نظام إدارة الصالات الرياضية وبوابات الدخول الذكية',
        lang_switch: 'Français',
        lang_name: 'العربية',
        lang_flag: '🇩🇿',

        // Navigation
        nav_dashboard: 'لوحة التحكم',
        nav_activities: 'الأنشطة والعمليات',
        nav_attendance: 'سجل الحضور',
        nav_gate: 'بوابات الدخول (RFID)',
        nav_members: 'الأعضاء والمشتركين',
        nav_branches: 'الفروع والقاعات',
        nav_subscriptions: 'الاشتراكات',
        nav_packages: 'الباقات والعروض',
        nav_services: 'الخدمات الرياضية',
        nav_cycles: 'فترات التجديد',
        nav_users: 'المستخدمين والمدراء',
        nav_settings: 'الإعدادات',
        nav_logout: 'تسجيل الخروج',

        // Auth
        login_title: 'تسجيل الدخول',
        login_subtitle: 'يرجى إدخال بياناتك للمتابعة إلى النظام',
        login_email: 'البريد الإلكتروني',
        login_password: 'كلمة المرور',
        login_btn: 'تسجيل الدخول',
        login_btn_loading: 'جاري تسجيل الدخول...',
        login_forgot: 'نسيت كلمة المرور؟',
        login_success: 'تم تسجيل الدخول بنجاح! جاري التحويل...',
        login_failed: 'فشل تسجيل الدخول، تأكد من صحة البريد أو كلمة المرور',
        login_no_admin: 'هذا الحساب ليس لديه صلاحيات الإدارة',

        // Gate Kiosk
        gate_title: 'شاشة بوابات الدخول الذكية',
        gate_subtitle: 'نظام التحقق الآلي والتحكم في الدخول عبر بطاقات RFID',
        gate_kiosk_badge: 'وضع البوابات المباشر (KIOSK MODE)',
        gate_waiting_title: 'البوابة جاهزة للعبور',
        gate_waiting_subtitle: 'يرجى تمرير بطاقة الدخول الذكية (RFID) بالقرب من القارئ',
        gate_scanning: 'جاري التحقق من البطاقة وبيانات الاشتراك...',
        gate_access_granted: 'تم السماح بالدخول',
        gate_access_denied: 'عذراً! تم رفض الدخول',
        gate_verified_success: 'تم التحقق بنجاح، تفضل بالدخول وتمرين موفق!',
        gate_already_checked_in: 'العضو سجل دخوله بالفعل منذ لحظات، تفضل بالدخول!',
        gate_card_not_registered: 'البطاقة غير مسجلة في النظام! يرجى مراجعة موظف الاستقبال',
        gate_account_inactive: 'حساب العضو غير نشط أو موقوف! يرجى مراجعة الإدارة',
        gate_sub_expired: 'الاشتراك منتهي الصلاحية! يرجى تجديد الاشتراك للمتابعة',
        gate_sub_none: 'لا يوجد اشتراك نشط لهذا العضو! يرجى مراجعة الاستقبال',

        // Gate Member Card Details
        gate_member_name: 'اسم العضو',
        gate_account_num: 'رقم العضوية',
        gate_rfid_card: 'رقم بطاقة RFID',
        gate_status: 'حالة الحساب',
        gate_package: 'الباقة الرياضية',
        gate_cycle: 'النوع',
        gate_expires_at: 'تاريخ الانتهاء',
        gate_days_left: 'الأيام المتبقية',
        gate_sessions_left: 'الحصص المتبقية',
        gate_total_sessions: 'إجمالي الحصص',
        gate_unlimited_sessions: 'حصص غير محدودة',
        gate_sessions_depleted: 'عذراً! لقد استنفدت جميع الحصص المتاحة في اشتراكك',
        gate_duration_expired: 'عذراً! انتهت مدة صلاحية الاشتراك',
        gate_sessions: 'حصة',
        gate_session: 'حصة',
        gate_used: 'مستهلكة',
        gate_branch: 'القاعة / الفرع',
        gate_checkin_time: 'وقت الدخول',
        gate_day: 'يوم',
        gate_days: 'أيام',
        gate_active: 'نشط ومفعل',
        gate_inactive: 'غير مفعل',
        gate_expired: 'منتهي',

        // Gate Controls
        gate_sound_on: 'الصوت مفعل',
        gate_sound_muted: 'الصوت مكتوم',
        gate_fullscreen: 'ملء الشاشة',
        gate_exit_fullscreen: 'إلغاء ملء الشاشة',
        gate_reset: 'إعادة ضبط الشاشة',
        gate_auto_reset: 'إعادة التهيؤ التلقائي خلال',
        gate_seconds: 'ثانية',
        gate_manual_placeholder: 'أدخل رقم البطاقة أو رقم العضو...',
        gate_check_btn: 'فحص',
        gate_test_title: 'اختبار تجريبي سريع',
        gate_test_active: 'بطاقة نشطة (محمد أحمد)',
        gate_test_second: 'بطاقة مفتوحة (سارة كريم)',
        gate_test_depleted: 'حصص مكتملة (ياسين)',
        gate_test_expired: 'مدة منتهية (خالد)',
        gate_test_unregistered: 'بطاقة غير مسجلة',

        // Gate Stats & Feed
        gate_today_stats: 'إحصائيات اليوم',
        gate_today_checkins: 'إجمالي الدخول اليوم',
        gate_current_hall: 'القاعة الحالية',
        gate_select_branch: 'جميع الفروع والقاعات',
        gate_recent_feed: 'آخر عمليات الدخول المسجلة اليوم',
        gate_no_checkins_yet: 'لا توجد عمليات دخول مسجلة اليوم حتى الآن',

        // Subscriptions Management
        sub_title: 'اشتراك العضو',
        sub_package: 'الباقة / العرض الرياضي',
        sub_interval: 'معدل التكرار / عدد الفترات',
        sub_sessions_type: 'نوع الحصص',
        sub_sessions_limited: 'عدد حصص محدد',
        sub_sessions_unlimited: 'حصص مفتوحة (غير محدودة)',
        sub_total_sessions: 'إجمالي الحصص',
        sub_remaining_sessions: 'الحصص المتبقية',
        sub_sessions_used: 'الحصص المستهلكة',
        sub_add_sessions: 'إضافة حصص تعويضية',
        sub_custom_expiry: 'تاريخ انتهاء مخصص',
        sub_use_custom_expiry: 'تحديد تاريخ انتهاء مخصص',
        sub_calculated_expiry: 'تاريخ الانتهاء المحسوب تلقائياً',
        sub_quick_add_days: 'تمديد سريع للأيام',
        sub_days_plus_7: '+7 أيام',
        sub_days_plus_15: '+15 يوماً',
        sub_days_plus_30: '+30 يوماً',
        sub_status: 'حالة الاشتراك',
        sub_status_active: 'نشط',
        sub_status_inactive: 'غير نشط',
        sub_status_suspended: 'موقوف مؤقتاً',
        sub_status_cancelled: 'ملغى',
        sub_status_completed: 'مكتمل (استنفد الحصص)',
        sub_status_expired: 'منتهي الصلاحية',
        sub_valid_until: 'صالح لغاية',
        sub_starts_at: 'تاريخ البداية (اليوم تلقائياً)',
        sub_renew: 'تجديد الاشتراك',
        sub_renew_confirm: 'هل أنت متأكد من تجديد هذا الاشتراك وتمديد تاريخ الانتهاء وإعادة تعبئة الحصص؟',
        sub_renew_success: 'تم تجديد الاشتراك بنجاح',
        sub_auto_calculated: 'محسوب تلقائياً من تاريخ البداية ومدة الباقة',
        sub_sessions_badge: 'الحصص',
        sub_sessions_progress: 'مستهلكة من',

        // Common
        common_all: 'الكل',
        common_search: 'بحث...',
        common_save: 'حفظ',
        common_cancel: 'إلغاء',
        common_delete: 'حذف',
        common_edit: 'تعديل',
        common_add: 'إضافة',
        common_actions: 'الإجراءات',
        common_loading: 'جاري التحميل...',
        common_success: 'تمت العملية بنجاح',
        common_error: 'حدث خطأ ما',
        common_name: 'الاسم',
        common_email: 'البريد',
        common_phone: 'الهاتف',
        common_date: 'التاريخ',
        common_time: 'الوقت',
        common_total: 'الإجمالي'
    },
    fr: {
        // App
        app_name: 'LaraGym',
        app_tagline: 'Système de gestion de salle de sport & contrôle d\'accès RFID',
        lang_switch: 'العربية',
        lang_name: 'Français',
        lang_flag: '🇫🇷',

        // Navigation
        nav_dashboard: 'Tableau de bord',
        nav_activities: 'Activités & Opérations',
        nav_attendance: 'Présences',
        nav_gate: 'Accès Portiques (RFID)',
        nav_members: 'Adhérents & Membres',
        nav_branches: 'Succursales & Salles',
        nav_subscriptions: 'Abonnements',
        nav_packages: 'Forfaits & Offres',
        nav_services: 'Services sportifs',
        nav_cycles: 'Cycles de facturation',
        nav_users: 'Utilisateurs & Admins',
        nav_settings: 'Paramètres',
        nav_logout: 'Déconnexion',

        // Auth
        login_title: 'Connexion',
        login_subtitle: 'Veuillez saisir vos identifiants pour accéder au système',
        login_email: 'Adresse e-mail',
        login_password: 'Mot de passe',
        login_btn: 'Se connecter',
        login_btn_loading: 'Connexion en cours...',
        login_forgot: 'Mot de passe oublié ?',
        login_success: 'Connexion réussie ! Redirection...',
        login_failed: 'Échec de connexion, veuillez vérifier vos identifiants',
        login_no_admin: 'Ce compte ne possède pas les privilèges administrateur',

        // Gate Kiosk
        gate_title: 'Contrôle d\'Accès Portiques Intelligents',
        gate_subtitle: 'Vérification automatique et contrôle des entrées par badge RFID',
        gate_kiosk_badge: 'MODE KIOSQUE EN DIRECT',
        gate_waiting_title: 'Portique Prêt pour l\'Accès',
        gate_waiting_subtitle: 'Veuillez scanner votre carte RFID près du lecteur',
        gate_scanning: 'Vérification de la carte et du statut de l\'abonnement...',
        gate_access_granted: 'Accès Autorisé',
        gate_access_denied: 'Accès Refusé',
        gate_verified_success: 'Vérification réussie, bienvenue et bonne séance !',
        gate_already_checked_in: 'Passage déjà enregistré il y a quelques instants. Bienvenue !',
        gate_card_not_registered: 'Badge non enregistré dans le système ! Veuillez voir la réception',
        gate_account_inactive: 'Compte adhérent inactif ou suspendu ! Contactez la direction',
        gate_sub_expired: 'Abonnement expiré ! Veuillez renouveler pour accéder à la salle',
        gate_sub_none: 'Aucun abonnement actif trouvé pour cet adhérent',

        // Gate Member Card Details
        gate_member_name: 'Nom de l\'adhérent',
        gate_account_num: 'N° Adhérent',
        gate_rfid_card: 'N° Badge RFID',
        gate_status: 'Statut du compte',
        gate_package: 'Formule / Forfait',
        gate_cycle: 'Durée',
        gate_expires_at: 'Date d\'expiration',
        gate_days_left: 'Jours restants',
        gate_sessions_left: 'Séances restantes',
        gate_total_sessions: 'Total des séances',
        gate_unlimited_sessions: 'Séances illimitées',
        gate_sessions_depleted: 'Désolé ! Toutes les séances de votre abonnement ont été consommées',
        gate_duration_expired: 'Désolé ! La durée de validité de votre abonnement a expiré',
        gate_sessions: 'séances',
        gate_session: 'séance',
        gate_used: 'utilisées',
        gate_branch: 'Salle / Succursale',
        gate_checkin_time: 'Heure de passage',
        gate_day: 'jour',
        gate_days: 'jours',
        gate_active: 'Actif & En règle',
        gate_inactive: 'Inactif',
        gate_expired: 'Expiré',

        // Gate Controls
        gate_sound_on: 'Son activé',
        gate_sound_muted: 'Son coupé',
        gate_fullscreen: 'Plein écran',
        gate_exit_fullscreen: 'Quitter plein écran',
        gate_reset: 'Réinitialiser',
        gate_auto_reset: 'Réinitialisation automatique dans',
        gate_seconds: 'sec',
        gate_manual_placeholder: 'Numéro de badge ou n° adhérent...',
        gate_check_btn: 'Vérifier',
        gate_test_title: 'Tests rapides de simulation',
        gate_test_active: 'Badge actif (Mohamed Ahmed)',
        gate_test_second: 'Badge illimité (Sara Karim)',
        gate_test_depleted: 'Séances épuisées (Yassine)',
        gate_test_expired: 'Durée expirée (Khaled)',
        gate_test_unregistered: 'Badge non reconnu',

        // Gate Stats & Feed
        gate_today_stats: 'Statistiques du jour',
        gate_today_checkins: 'Total entrées aujourd\'hui',
        gate_current_hall: 'Salle actuelle',
        gate_select_branch: 'Toutes les succursales',
        gate_recent_feed: 'Derniers passages enregistrés aujourd\'hui',
        gate_no_checkins_yet: 'Aucun passage enregistré pour le moment',

        // Subscriptions Management
        sub_title: 'Abonnement Adhérent',
        sub_package: 'Formule / Forfait sport',
        sub_interval: 'Intervalle / Nombre de cycles',
        sub_sessions_type: 'Type de séances',
        sub_sessions_limited: 'Nombre de séances limité',
        sub_sessions_unlimited: 'Séances illimitées',
        sub_total_sessions: 'Total des séances',
        sub_remaining_sessions: 'Séances restantes',
        sub_sessions_used: 'Séances consommées',
        sub_add_sessions: 'Ajout de séances de compensation',
        sub_custom_expiry: 'Date d\'expiration personnalisée',
        sub_use_custom_expiry: 'Définir une date d\'expiration personnalisée',
        sub_calculated_expiry: 'Date d\'expiration calculée automatiquement',
        sub_quick_add_days: 'Prolongation rapide de jours',
        sub_days_plus_7: '+7 jours',
        sub_days_plus_15: '+15 jours',
        sub_days_plus_30: '+30 jours',
        sub_status: 'Statut de l\'abonnement',
        sub_status_active: 'Actif',
        sub_status_inactive: 'Inactif',
        sub_status_suspended: 'Suspendu',
        sub_status_cancelled: 'Annulé',
        sub_status_completed: 'Complété (séances épuisées)',
        sub_status_expired: 'Expiré',
        sub_valid_until: 'Valable jusqu\'au',
        sub_starts_at: 'Date de début (Aujourd\'hui par défaut)',
        sub_renew: 'Renouveler l\'abonnement',
        sub_renew_confirm: 'Confirmer le renouvellement de cet abonnement et la réinitialisation des séances ?',
        sub_renew_success: 'Abonnement renouvelé avec succès',
        sub_auto_calculated: 'Calculé automatiquement à partir de la date de début et du cycle',
        sub_sessions_badge: 'Séances',
        sub_sessions_progress: 'consommées sur',

        // Common
        common_all: 'Tous',
        common_search: 'Rechercher...',
        common_save: 'Enregistrer',
        common_cancel: 'Annuler',
        common_delete: 'Supprimer',
        common_edit: 'Modifier',
        common_add: 'Ajouter',
        common_actions: 'Actions',
        common_loading: 'Chargement en cours...',
        common_success: 'Opération réussie',
        common_error: 'Une erreur s\'est produite',
        common_name: 'Nom',
        common_email: 'E-mail',
        common_phone: 'Téléphone',
        common_date: 'Date',
        common_time: 'Heure',
        common_total: 'Total'
    }
};

translations.en = {"app_name": "LaraGym", "app_tagline": "Gym memberships and access management", "lang_switch": "Language", "lang_name": "English", "lang_flag": "EN", "nav_dashboard": "Overview", "nav_activities": "Activity log", "nav_attendance": "Attendance", "nav_gate": "Access gate", "nav_members": "Members", "nav_branches": "Branches", "nav_subscriptions": "Memberships", "nav_packages": "Plans and packages", "nav_services": "Gym services", "nav_cycles": "Membership periods", "nav_users": "Staff and administrators", "nav_settings": "Settings", "nav_logout": "Sign out", "login_title": "Sign in", "login_subtitle": "Enter your credentials to continue", "login_email": "Email address", "login_password": "Password", "login_btn": "Sign in", "login_btn_loading": "Signing in…", "login_forgot": "Forgot password?", "login_success": "Signed in successfully", "login_failed": "Sign-in failed. Check your credentials.", "login_no_admin": "This account does not have staff access", "common_all": "All", "common_search": "Search…", "common_save": "Save", "common_cancel": "Cancel", "common_delete": "Delete", "common_edit": "Edit", "common_add": "Add", "common_actions": "Actions", "common_loading": "Loading…", "common_success": "Saved successfully", "common_error": "Something went wrong", "common_name": "Name", "common_email": "Email", "common_phone": "Phone", "common_date": "Date", "common_time": "Time", "common_total": "Total", "gate_title": "Access control", "gate_subtitle": "Check membership eligibility and record arrivals", "gate_kiosk_badge": "Reception mode", "gate_waiting_title": "Ready to check in", "gate_waiting_subtitle": "Scan your membership card at the reader", "gate_scanning": "Checking card and membership…", "gate_access_granted": "Access granted", "gate_access_denied": "Access denied", "gate_verified_success": "Welcome. Enjoy your workout!", "gate_already_checked_in": "This arrival has already been recorded", "gate_card_not_registered": "Card not registered. Please contact reception.", "gate_account_inactive": "Account inactive. Please contact reception.", "gate_sub_expired": "Membership expired. Please renew.", "gate_sub_none": "No active membership", "gate_member_name": "Member name", "gate_account_num": "Member number", "gate_rfid_card": "RFID card", "gate_status": "Account status", "gate_package": "Membership plan", "gate_cycle": "Period", "gate_expires_at": "Expiry date", "gate_days_left": "Days remaining", "gate_sessions_left": "Sessions remaining", "gate_total_sessions": "Total sessions", "gate_unlimited_sessions": "Unlimited sessions", "gate_sessions_depleted": "All sessions have been used. Please renew.", "gate_duration_expired": "Membership validity has ended", "gate_sessions": "sessions", "gate_session": "session", "gate_used": "used", "gate_branch": "Branch", "gate_checkin_time": "Arrival time", "gate_day": "day", "gate_days": "days", "gate_active": "Active", "gate_inactive": "Inactive", "gate_expired": "Expired", "gate_sound_on": "Sound on", "gate_sound_muted": "Sound muted", "gate_fullscreen": "Full screen", "gate_exit_fullscreen": "Exit full screen", "gate_reset": "Reset screen", "gate_auto_reset": "Resetting in", "gate_seconds": "seconds", "gate_manual_placeholder": "Enter a card or member number…", "gate_check_btn": "Check access", "gate_test_title": "Sample cards", "gate_test_active": "Active card", "gate_test_second": "Unlimited membership", "gate_test_depleted": "Sessions depleted", "gate_test_expired": "Expired membership", "gate_test_unregistered": "Unregistered card", "gate_today_stats": "Today at the gym", "gate_today_checkins": "Today’s visits", "gate_current_hall": "Current branch", "gate_select_branch": "Select a branch", "gate_recent_feed": "Recent arrivals today", "gate_no_checkins_yet": "No arrivals today", "sub_title": "Membership", "sub_package": "Plan", "sub_interval": "Number of periods", "sub_sessions_type": "Session allowance", "sub_sessions_limited": "Limited sessions", "sub_sessions_unlimited": "Unlimited sessions", "sub_total_sessions": "Total sessions", "sub_remaining_sessions": "Sessions remaining", "sub_sessions_used": "Sessions used", "sub_add_sessions": "Adjust session credit", "sub_custom_expiry": "Custom expiry", "sub_use_custom_expiry": "Set a custom expiry", "sub_calculated_expiry": "Calculated expiry", "sub_quick_add_days": "Extend validity", "sub_days_plus_7": "+7 days", "sub_days_plus_15": "+15 days", "sub_days_plus_30": "+30 days", "sub_status": "Membership status", "sub_valid_until": "Valid until", "sub_starts_at": "Start date", "sub_renew": "Renew membership", "sub_renew_success": "Membership renewed", "sub_auto_calculated": "Calculated from start date and plan duration", "sub_sessions_badge": "Sessions", "sub_sessions_progress": "used of"};
for (const lang of ["ar", "fr", "en"]) Object.assign(translations[lang], gymCopy[lang]);

// Reactive translation store
export const t = derived(currentLang, ($lang) => {
    return (key, fallback = '') => {
        const langDict = translations[$lang] || translations.ar;
        if (key in langDict) return langDict[key];
        const arDict = $lang === 'en' ? translations.en : translations.ar;
        if (key in arDict) return arDict[key];
        return fallback || key;
    };
});

// Set language helper
export function setLanguage(lang) {
    if (!['ar', 'fr', 'en'].includes(lang)) return;
    currentLang.set(lang);
    if (typeof window !== 'undefined') {
        localStorage.setItem('laragym_lang', lang);
        document.documentElement.setAttribute('lang', lang);
        document.documentElement.setAttribute('dir', lang === 'ar' ? 'rtl' : 'ltr');
    }
}

// Toggle between Arabic and French
export function toggleLanguage() {
    let newLang = 'ar';
    currentLang.update((cur) => {
        newLang = cur === 'ar' ? 'fr' : 'ar';
        return newLang;
    });
    if (typeof window !== 'undefined') {
        localStorage.setItem('laragym_lang', newLang);
        document.documentElement.setAttribute('lang', newLang);
        document.documentElement.setAttribute('dir', newLang === 'ar' ? 'rtl' : 'ltr');
    }
    return newLang;
}
