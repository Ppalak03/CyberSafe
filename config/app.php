<?php
// Application-wide settings and the XP / level system (single source of truth).
declare(strict_types=1);

const APP_NAME        = 'CyberSafe';
const BASE_URL        = '/CyberSafe';          // folder name under htdocs; change if you rename it
const SESSION_TIMEOUT = 1800;                  // 30 min of inactivity
const LOGIN_MAX_FAILS = 5;                     // per email+IP ...
const LOGIN_WINDOW    = 900;                   // ... within 15 minutes
const POINTS_CORRECT  = 100;
const XP_CORRECT      = 10;                    // awarded once per scenario (first correct attempt)

// Level thresholds (minimum total XP). Master = 1000 XP, matching the Master badge.
const LEVELS = [
    1 => ['name' => 'Cyber Beginner',    'min_xp' => 0],
    2 => ['name' => 'Digital Defender',  'min_xp' => 100],
    3 => ['name' => 'Threat Spotter',    'min_xp' => 250],
    4 => ['name' => 'Phishing Hunter',   'min_xp' => 450],
    5 => ['name' => 'Cyber Detective',   'min_xp' => 700],
    6 => ['name' => 'CyberSafe Master',  'min_xp' => 1000],
];

// Badge rules (see includes/gamification.php)
const BADGE_MIN_ATTEMPTS      = 20;               // Cyber Detective needs this many attempts first
const BADGE_PHISHING_CATEGORY = 'Phishing Emails'; // category counted for the Phishing Hunter badge

// Security / environment switches
const APP_DEBUG = false;                  // true only on your own machine while debugging (shows PHP errors on screen)
const CSP_ENABLED = true;                 // Content-Security-Policy header; set false if a custom change gets blocked
const LOGIN_MAX_FAILS_PER_IP = 20;        // failed logins per IP (any email) in LOGIN_WINDOW
