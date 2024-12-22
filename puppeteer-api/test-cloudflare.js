const express = require('express');
const puppeteer = require('puppeteer');

const app = express();
const port = 6666; // Thay đổi port thành 6666

// app.get('/fetch-html', async (req, res) => {
//     const url = req.query.url;
//     const html = await getHtmlContent(url);
//     res.send(html);
// });

function delay(timeout) {
    return new Promise((resolve) => {
        setTimeout(resolve, timeout);
    });
}

async function crawlWebsite(url) {
    const browser = await puppeteer.launch();
    const page = await browser.newPage();

    try {
        await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        await page.goto(url, {
            waitUntil: 'networkidle0',
            referer: 'https://truyenvn.xyz'
        });

        const chapterReadmoreButton = await page.$('div.c-chapter-readmore');
        if (chapterReadmoreButton) {
            await chapterReadmoreButton.click();
            await delay(3000);
        }

        const htmlContent = await page.content();
        return htmlContent;
    } finally {
        await browser.close();
    }
}


// Sử dụng function crawlWebsite
const url = process.argv[2];
crawlWebsite(url).then((html) => {
    console.log(html);
}).catch((err) => {
    // console.error(err);
});

 