const { execSync } = require('child_process');
const path = require('path');
const fs = require('fs');

const edgePath = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const outputDir = path.resolve(__dirname, 'images', 'screenshots');
if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true });
}

const profileDir = 'C:\\temp\\edge_profile';

const items = [
    { name: 'p1_fig1_sitemap.png', url: 'file:///' + path.resolve(__dirname, 'diagram_sitemap.html').replace(/\\/g, '/'), size: '900,420' },
    { name: 'p1_fig2_dashboard.png', url: 'http://localhost:8000/pages/dashboard.php?as_role=student', size: '1200,680' },
    { name: 'p2_fig1_about.png', url: 'http://localhost:8000/pages/about.html', size: '1100,620' },
    { name: 'p2_fig2_contact.png', url: 'http://localhost:8000/pages/contact.html', size: '1100,640' },
    { name: 'p2_fig3_profile.png', url: 'http://localhost:8000/pages/profile.html', size: '1100,560' },
    { name: 'p3_fig1_desktop.png', url: 'http://localhost:8000/pages/dashboard.php?as_role=student', size: '1280,680' },
    { name: 'p3_fig2_tablet.png', url: 'http://localhost:8000/pages/dashboard.php?as_role=student', size: '768,680' },
    { name: 'p3_fig3_mobile.png', url: 'http://localhost:8000/pages/dashboard.php?as_role=student', size: '375,680' },
    { name: 'p4_fig1_accordion.png', url: 'http://localhost:8000/pages/about.html', size: '1100,620' },
    { name: 'p4_fig2_slider.png', url: 'http://localhost:8000/pages/dashboard.php?as_role=student', size: '1100,660' },
    { name: 'p5_fig1_register.png', url: 'http://localhost:8000/pages/register.html', size: '1100,680' },
    { name: 'p6_fig1_events.png', url: 'http://localhost:8000/pages/events.html', size: '1100,650' },
    { name: 'p7_fig1_contact.png', url: 'http://localhost:8000/pages/contact.html', size: '1100,640' },
    { name: 'p7_fig2_success.png', url: 'http://localhost:8000/php/contact.php?preview=success', size: '1100,560' },
    { name: 'p8_fig1_er_diagram.png', url: 'file:///' + path.resolve(__dirname, 'diagram_er.html').replace(/\\/g, '/'), size: '920,480' },
    { name: 'p8_fig2_pdo_test.png', url: 'http://localhost:8000/php/test_pdo.php', size: '1000,560' },
    { name: 'p9_fig1_register.png', url: 'http://localhost:8000/pages/register.html', size: '1100,680' },
    { name: 'p9_fig2_success.png', url: 'http://localhost:8000/php/register.php?preview=success', size: '1100,560' },
    { name: 'p10_fig1_login.png', url: 'http://localhost:8000/pages/login.html', size: '1000,600' },
    { name: 'p10_fig2_auth_dash.png', url: 'http://localhost:8000/pages/dashboard.php?as_role=student', size: '1100,660' },
    { name: 'p10_fig3_unauth.png', url: 'http://localhost:8000/pages/login.html?msg=unauthorized', size: '1000,600' },
    { name: 'p11_fig1_students.png', url: 'http://localhost:8000/pages/manage_students.php?as_role=admin', size: '1180,680' },
    { name: 'p11_fig2_filtered.png', url: 'http://localhost:8000/pages/manage_students.php?as_role=admin&course=BCA', size: '1180,680' },
    { name: 'p12_fig1_events.png', url: 'http://localhost:8000/pages/manage_events.php?as_role=admin', size: '1180,680' },
    { name: 'p13_fig1_api.png', url: 'http://localhost:8000/pages/events_api.php?as_role=admin', size: '1180,680' },
    { name: 'p14_fig1_admin_dash.png', url: 'http://localhost:8000/pages/admin_dashboard.php?as_role=admin', size: '1180,680' }
];

console.log(`Starting capture of ${items.length} screenshots...`);

for (let i = 0; i < items.length; i++) {
    const item = items[i];
    const targetFile = path.join(outputDir, item.name);
    const cmd = `"${edgePath}" --headless=new --user-data-dir="${profileDir}" --disable-gpu --screenshot="${targetFile}" --window-size=${item.size} "${item.url}"`;
    try {
        execSync(cmd, { stdio: 'pipe' });
        const exists = fs.existsSync(targetFile);
        const sz = exists ? fs.statSync(targetFile).size : 0;
        console.log(`[${i+1}/${items.length}] Captured ${item.name} (${sz} bytes)`);
    } catch (err) {
        console.error(`Error capturing ${item.name}:`, err.message);
    }
}

console.log('All screenshots captured!');
