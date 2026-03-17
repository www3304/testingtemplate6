<?php
// 获取导航
$navStmt = $pdo->prepare("SELECT nav_name, nav_value FROM {$prefix}companyNavigation WHERE language_id = ?");
$navStmt->execute([$language_id]);
$navLinks = $navStmt->fetchAll(PDO::FETCH_ASSOC);

//fetch subnav
$subnavStmt = $pdo->prepare("SELECT subnav_name, subnav_link FROM {$prefix}companySubnav WHERE language_id = ?");
$subnavStmt->execute([$language_id]);
$subnavs = $subnavStmt->fetchAll(PDO::FETCH_ASSOC);

$navIcons = [
    "home"     => "icons/home.png",
    "about"    => "icons/about.png",
    "features" => "icons/features.png",
    "provide"  => "icons/provides.png",
    "gallery"  => "icons/gallery.png",
    "video"    => "icons/video.png",
    "contact"  => "icons/contact.png",
    "blog"     => "icons/blog.png",
    "subnav"   => "icons/subnav.png"
]
?>

<link href="css/header.css" rel="stylesheet">

<header>
    <!-- <div class="menu-overlay" id="menuOverlay" onclick="toggleMenu()"></div> -->
    <div class="header-container">
        <a href="/"><img src="<?= htmlspecialchars($company['header_logo'] ?? $company['logo']) ?>" alt="Company Logo" class="logo"></a>

        <button class="menu-toggle" onclick="toggleMenu()">☰</button>

        <nav id="mainNav">
            <?php foreach ($navLinks as $nav): ?>
                <?php
                $sectionKey = strtolower($nav['nav_value']);
                if ($sectionKey === 'home' || isSectionActive($sectionKey, $sectionStatus)):
                    $iconPath = isset($navIcons[$sectionKey]) ? $navIcons[$sectionKey] : "";
                    $iconTag = $iconPath ? "<img src='{$iconPath}' alt='{$sectionKey}' class='nav-icon'>" : "";
                ?>
                    <?php if ($sectionKey === 'contact'): ?>
                        <a href="#contact" onclick="toggleSocials(); return false;">
                            <?= $iconTag . " " . htmlspecialchars($nav['nav_name']) ?>
                        </a>

                    <?php elseif ($sectionKey === 'subnav'): ?>
                        <div class="dropdown">
                            <a href="javascript:void(0)"><?= $iconTag . " " . htmlspecialchars($nav['nav_name']) ?></a>
                            <ul class="dropdown-menu">
                                <?php foreach ($subnavs as $subnav): ?>
                                    <li>
                                        <?php if (!empty($subnav['subnav_link'])): ?>
                                            <?php 
                                                $link = $subnav['subnav_link'];
                                                if (!preg_match('#^https?://#i', $link)) $link = 'https://' . $link;
                                            ?>
                                            <a href="<?= htmlspecialchars($link) ?>" target="_blank" rel="noopener noreferrer">
                                                <?= htmlspecialchars($subnav['subnav_name']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span><?= htmlspecialchars($subnav['subnav_name']) ?></span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="<?= $sectionKey === 'home' ? '#' : "#{$sectionKey}" ?>"
                        <?= $sectionKey === 'home' ? 'onclick="window.scrollTo({top: 0, behavior: \'smooth\'}); return false;"' : '' ?>>
                            <?= $iconTag . " " . htmlspecialchars($nav['nav_name']) ?>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <?php
        if (strtolower($company['googletranslate'] ?? '') === 'no') {
            // 用 PDO 取语言列表
            $stmt = $pdo->prepare("SELECT id, language FROM {$prefix}companyLanguages ORDER BY id ASC");
            $stmt->execute();
            $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <!-- 用户自定义语言选择 -->
            <form method="get" id="customLangForm" class="lang-selector">
                <select name="lang" id="customLang" onchange="document.getElementById('customLangForm').submit();">
                    <?php foreach ($languages as $lang): ?>
                        <option value="<?= htmlspecialchars($lang['id']) ?>" <?= (isset($_GET['lang']) && $_GET['lang'] == $lang['id']) ? 'selected' : '' ?>>
                            <?= strtoupper(htmlspecialchars($lang['language'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php
        } else {
        ?>
            <!-- Google Translate 语言选择器 -->
            <div id="google_translate_element" class="lang-selector"></div>
        <?php
        }
        ?>
    </div>
</header>

<script>
    function toggleMenu() {
        const nav = document.getElementById('mainNav');
        const overlay = document.getElementById('menuOverlay');
        const pageContent = document.getElementById('pageContent');

        const isOpening = !nav.classList.contains('show');

        if (isOpening) {
            nav.classList.add('show');
            overlay.classList.add('active');
            pageContent.classList.add('shifted');
            if (window.innerWidth <= 768) {
                document.documentElement.classList.add('menu-open'); // ✅ 锁定 html
                document.body.classList.add('menu-open'); // ✅ 锁定 body
            }
        } else {
            nav.classList.remove('show');
            overlay.classList.remove('active');
            pageContent.classList.remove('shifted');
            if (window.innerWidth <= 768) {
                document.documentElement.classList.remove('menu-open'); // ✅ 解锁 html
                document.body.classList.remove('menu-open'); // ✅ 解锁 body
            }
        }
    }


    // 点任意 nav 链接后关闭菜单
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('#mainNav a[href^="#"]').forEach(a => {
            a.addEventListener('click', e => {
                e.preventDefault(); // 阻止浏览器直接跳
                const targetId = a.getAttribute('href').substring(1);
                const target = document.getElementById(targetId);

                // 先关闭菜单
                toggleMenu();

                // 等菜单收起动画完成再滚动
                setTimeout(() => {
                    if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 300);
            });
        });
    });

    window.addEventListener('resize', () => {
        const nav = document.getElementById("mainNav");
        const translate = document.getElementById("google_translate_element") ||
            document.getElementById("customLangForm");

        if (!translate) return;

        if (window.innerWidth > 768) {
            translate.style.display = "block";
            document.querySelector('.header-container').appendChild(translate);
        } else {
            if (!nav.classList.contains("show")) {
                translate.style.display = "none";
                document.querySelector('.header-container').appendChild(translate);
            }
        }
    });

    // document.addEventListener('DOMContentLoaded', () => {
    //     document.querySelectorAll('#mainNav a').forEach(a => {
    //         a.addEventListener('click', () => closeMenu());
    //     });
    // });

    function closeMenu() {
        document.getElementById("mainNav").classList.remove("show");
        document.getElementById("menuOverlay").classList.remove("active");
        document.getElementById("pageContent").classList.remove("shifted");
    }

    document.addEventListener("DOMContentLoaded", () => {
        const nav = document.getElementById("mainNav");
        const headerContainer = document.querySelector(".header-container");
        const customLangForm = document.getElementById("customLangForm");
        const googleTranslate = document.getElementById("google_translate_element");

        function moveLangToSidebar() {
            if (window.innerWidth <= 768) {
                const target = customLangForm || googleTranslate;
                if (target && !nav.contains(target)) {
                    nav.appendChild(target); // 移动到 sidebar nav 下面
                    target.style.display = "block"; // 显示
                }
            } else {
                const target = customLangForm || googleTranslate;
                if (target && !headerContainer.contains(target)) {
                    headerContainer.appendChild(target); // 放回 header
                    target.style.display = ""; // 恢复默认
                }
            }
        }

        // 页面加载时运行一次
        moveLangToSidebar();

        // 监听窗口变化
        window.addEventListener("resize", moveLangToSidebar);
    });
</script>

<?php if (strtolower($company['googletranslate'] ?? '') !== 'no'): ?>
    <script>
        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                includedLanguages: '<?= htmlspecialchars($company['googleincludedlanguages']) ?>',
                autoDisplay: false,
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE
            }, 'google_translate_element');
        }
    </script>
    <script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
<?php endif; ?>

<style>
    body {
        top: 0px !important;
    }

    /* 隐藏 Google 图标 */
    .goog-te-gadget-icon {
        display: none !important;
    }

    .VIpgJd-ZVi9od-ORHb-OEVmcd {
        display: none;
    }

    #google_translate_element img {
        height: 0px;
        max-height: 0px;
        max-width: 0px;
        object-fit: contain;
    }

    /* ✅ 彻底隐藏 google 的顶栏 iframe */
    .goog-te-banner-frame {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
    }

    body {
        top: 0px !important;
    }

    /* 隐藏 Google Translate 的小图标 */
    .goog-te-gadget-icon {
        display: none !important;
    }

    .VIpgJd-ZVi9od-ORHb-OEVmcd {
        display: none !important;
    }

    #google_translate_element img {
        height: 0px;
        max-height: 0px;
        max-width: 0px;
        object-fit: contain;
    }

    /* ✅ 打开 sidebar 时，锁定整个页面（html + body），避免 google iframe 影响 */
    html.menu-open,
    body.menu-open {
        position: fixed;
        width: 100%;
        overflow: hidden;
    }

    /* ✅ 美化 Google Translate 的语言选择 dropdown */
    .VIpgJd-ZVi9od-xl07Ob-lTBxed {
        display: inline-block;
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-color: #fff !important;
        border: 1px solid #d9d9d9 !important;
        border-radius: 4px !important;
        margin: 0 !important;
        padding: 6px 30px 6px 10px !important;
        font-size: 14px !important;
        font-family: Arial, sans-serif !important;
        color: #333 !important;
        cursor: pointer !important;
        text-decoration: none !important;

        background-image: url("data:image/svg+xml;utf8,<svg fill='%23333' height='16' viewBox='0 0 24 24' width='16' xmlns='http://www.w3.org/2000/svg'><path d='M7 10l5 5 5-5z'/></svg>");
        background-repeat: no-repeat;
        background-position: right 8px center;
        background-size: 16px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    /* hover 效果 */
    .VIpgJd-ZVi9od-xl07Ob-lTBxed:hover {
        border-color: #aaa !important;
    }

    /* focus 效果 */
    .VIpgJd-ZVi9od-xl07Ob-lTBxed:focus {
        outline: none !important;
        border-color: #4285f4 !important;
        box-shadow: 0 0 4px rgba(66, 133, 244, 0.4) !important;
    }

    /* 隐藏 Google Translate 默认的 ▼ 符号 */
    .VIpgJd-ZVi9od-xl07Ob-lTBxed span[aria-hidden="true"] {
        display: none !important;
    }

    /* 隐藏 Google Translate dropdown 里面的竖线分隔符 */
    .VIpgJd-ZVi9od-xl07Ob-lTBxed span[style*="border-left"] {
        display: none !important;
    }

    .goog-te-gadget-simple {
        background-color: #FFF;
        border-left: 0px solid #D5D5D5;
        border-top: 0px solid #9B9B9B;
        border-bottom: 0px solid #E8E8E8;
        border-right: 0px solid #D5D5D5;
        font-size: 10pt;
        display: inline-block;
        padding-top: 1px;
        padding-bottom: 2px;
        cursor: pointer;
    }
</style>