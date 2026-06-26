<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class System extends Model
{
    use HasFactory;

    protected $table = 'system';

    protected $fillable = [
        'name', 'logo', 'logo_dark', 'logo_icon', 'favicon',
        'slogan', 'timezone', 'date_format', 'time_format',
        'currency', 'currency_symbol', 'primary_color', 'secondary_color',
        'contact_email', 'contact_phone', 'address', 'location', 'meta_description',
        'meta_keywords', 'maintenance_mode', 'pagination_limit',
        'custom_css', 'custom_js', 'settings', 'website_pages', 'social_media'
    ];

    protected $casts = [
        'maintenance_mode' => 'boolean',
        'settings' => 'array',
        'website_pages' => 'array',
        'social_media' => 'array',
        'location' => 'array',

    ];

    // Default settings structure
    protected $attributes = [
        'settings' => '{
            "notifications": {
                "email_notifications": true,
                "push_notifications": true,
                "sms_notifications": false,
                "notification_sound": true
            },
            "security": {
                "two_factor_auth": false,
                "login_attempts": 5,
                "session_timeout": 30,
                "password_expiry": 90
            },
            "integrations": {
                "google_analytics": "",
                "google_maps_key": "",
                "mail_driver": "smtp",
                "mail_host": "",
                "mail_port": "587",
                "mail_username": "",
                "mail_password": ""
            },
            "backup": {
                "auto_backup": true,
                "backup_frequency": "daily",
                "backup_retention": 30,
                "backup_to_cloud": false
            },
            "company": {
                "website": "",
                "phone": "",
                "email": "",
                "address": "",
                "about": "",
                "mission": "",
                "vision": "",
                "values": ""
            }
        }',
        'website_pages' => '{
            "home": {
                "enabled": true,
                "title": "Home",
                "slug": "",
                "content": "",
                "meta_title": "",
                "meta_description": "",
                "meta_keywords": "",
                "show_in_menu": true,
                "order": 1
            },
            "about": {
                "enabled": true,
                "title": "About Us",
                "slug": "about",
                "content": "",
                "meta_title": "",
                "meta_description": "",
                "meta_keywords": "",
                "show_in_menu": true,
                "order": 2
            },
            "services": {
                "enabled": true,
                "title": "Services",
                "slug": "services",
                "content": "",
                "meta_title": "",
                "meta_description": "",
                "meta_keywords": "",
                "show_in_menu": true,
                "order": 3
            },
            "contact": {
                "enabled": true,
                "title": "Contact Us",
                "slug": "contact",
                "content": "",
                "meta_title": "",
                "meta_description": "",
                "meta_keywords": "",
                "show_in_menu": true,
                "order": 4
            },
            "faq": {
                "enabled": false,
                "title": "FAQ",
                "slug": "faq",
                "content": "",
                "meta_title": "",
                "meta_description": "",
                "meta_keywords": "",
                "show_in_menu": true,
                "order": 5
            },
            "privacy": {
                "enabled": true,
                "title": "Privacy Policy",
                "slug": "privacy-policy",
                "content": "",
                "meta_title": "",
                "meta_description": "",
                "meta_keywords": "",
                "show_in_menu": false,
                "order": 6
            },
            "terms": {
                "enabled": true,
                "title": "Terms of Service",
                "slug": "terms-of-service",
                "content": "",
                "meta_title": "",
                "meta_description": "",
                "meta_keywords": "",
                "show_in_menu": false,
                "order": 7
            }
        }',
        'social_media' => '{
            "facebook": {
                "enabled": false,
                "url": "",
                "icon": "ri-facebook-fill",
                "name": "Facebook",
                "color": "#1877F2",
                "order": 1
            },
            "twitter": {
                "enabled": false,
                "url": "",
                "icon": "ri-twitter-fill",
                "name": "Twitter",
                "color": "#1DA1F2",
                "order": 2
            },
            "instagram": {
                "enabled": false,
                "url": "",
                "icon": "ri-instagram-fill",
                "name": "Instagram",
                "color": "#E4405F",
                "order": 3
            },
            "linkedin": {
                "enabled": false,
                "url": "",
                "icon": "ri-linkedin-fill",
                "name": "LinkedIn",
                "color": "#0A66C2",
                "order": 4
            },
            "youtube": {
                "enabled": false,
                "url": "",
                "icon": "ri-youtube-fill",
                "name": "YouTube",
                "color": "#FF0000",
                "order": 5
            },
            "tiktok": {
                "enabled": false,
                "url": "",
                "icon": "ri-tiktok-fill",
                "name": "TikTok",
                "color": "#000000",
                "order": 6
            },
            "whatsapp": {
                "enabled": false,
                "url": "",
                "icon": "ri-whatsapp-fill",
                "name": "WhatsApp",
                "color": "#25D366",
                "order": 7
            },
            "telegram": {
                "enabled": false,
                "url": "",
                "icon": "ri-telegram-fill",
                "name": "Telegram",
                "color": "#26A5E4",
                "order": 8
            },
            "github": {
                "enabled": false,
                "url": "",
                "icon": "ri-github-fill",
                "name": "GitHub",
                "color": "#181717",
                "order": 9
            },
            "discord": {
                "enabled": false,
                "url": "",
                "icon": "ri-discord-fill",
                "name": "Discord",
                "color": "#5865F2",
                "order": 10
            },
            "slack": {
                "enabled": false,
                "url": "",
                "icon": "ri-slack-fill",
                "name": "Slack",
                "color": "#4A154B",
                "order": 11
            },
            "reddit": {
                "enabled": false,
                "url": "",
                "icon": "ri-reddit-fill",
                "name": "Reddit",
                "color": "#FF4500",
                "order": 12
            },
            "pinterest": {
                "enabled": false,
                "url": "",
                "icon": "ri-pinterest-fill",
                "name": "Pinterest",
                "color": "#BD081C",
                "order": 13
            },
            "snapchat": {
                "enabled": false,
                "url": "",
                "icon": "ri-snapchat-fill",
                "name": "Snapchat",
                "color": "#FFFC00",
                "order": 14
            },
            "skype": {
                "enabled": false,
                "url": "",
                "icon": "ri-skype-fill",
                "name": "Skype",
                "color": "#00AFF0",
                "order": 15
            }
        }',
        'location' => '{
            "country": "",
            "city": "",
            "name": "",
            "latitude": "",
            "longitude": ""
        }',
    ];

    public static function settings()
    {
        return self::firstOrCreate([], [
            'name' => config('app.name', 'Laravel'),
            'timezone' => config('app.timezone', 'UTC'),
            'date_format' => 'd-m-Y',
            'time_format' => 'H:i:s',
            'currency' => 'KES',
            'currency_symbol' => 'KSh',
            'primary_color' => '#3A57E8',
            'secondary_color' => '#08B1BA',
            'pagination_limit' => 15,
        ]);
    }
}