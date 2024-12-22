// const puppeteer = require('puppeteer');

// (async () => {
//     const browser = await puppeteer.launch({
//         args: ['--proxy-server=61.97.193.102:3128']
//     });
//     const page = await browser.newPage();
//     await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
//     await page.goto('https://nettruyencc.com');
//     await page.waitForSelector('link[rel="icon"][href="https://nettruyencc.com/public/assets/images/favicon.png"]', {timeout: 10000});
//     const content = await page.content();
//     console.log(content);
//     await browser.close();
// })();


const puppeteer = require('puppeteer');

async function fetchPageContent(url) {
    const browser = await puppeteer.launch({
        headless: true,
        args: [
            '--proxy-server=61.97.193.102:9070'
        ]
    });
    const page = await browser.newPage();

    // Cấu hình user agent
    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

    // Điều hướng đến URL
    await page.goto(url, { waitUntil: 'networkidle2' });

    try {
        // Đợi cho phần header tải xong
        await page.waitForSelector('header', { visible: true, timeout: 60000 });

        // Lấy biểu tượng favicon
        const faviconHref = await page.evaluate(() => {
            const favicon = document.querySelector('link[rel="icon"]');
            return favicon ? favicon.href : null;
        });

        // Xác minh biểu tượng favicon tải thành công
        if (faviconHref === 'https://nettruyencc.com/public/assets/images/favicon.png') {
            // Lấy nội dung HTML của trang web
            const content = await page.content();
            console.log(content);
            await browser.close();
            return content;
        } else {
            console.error('Favicon không tải đúng hoặc không tồn tại.');
            await browser.close();
            return null;
        }
    } catch (error) {
        console.error('Không thể tìm thấy phần header hoặc thời gian chờ đã hết.');
        console.error(error);
        await browser.close();
        return null;
    }
}

// Gọi hàm với URL mong muốn
const url = 'https://nettruyencc.com/tim-truyen?page=100';
fetchPageContent(url).then(content => {
    if (content) {
        console.log('Trang đã tải thành công và favicon đã tải.');
    } else {
        console.log('Không thể tải trang hoặc favicon không khớp.');
    }
});
