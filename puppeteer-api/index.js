const express = require("express");
const puppeteer = require("puppeteer");

const app = express();
const port = 6666; // Thay đổi port thành 6666

function delay(timeout) {
    return new Promise((resolve) => {
        setTimeout(resolve, timeout);
    });
}

async function crawlWebsite(url) {
    const browser = await puppeteer.launch();
    const page = await browser.newPage();

    try {
        await page.setUserAgent(
            "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36"
        );
        await page.goto(url, {
            waitUntil: "networkidle0",
            referer: "https://truyentranh.net.vn/",
        });

        await delay(400);

        const buttonSelector =
            "button.text-\\#286b86.dark\\:bg-\\#ccc.font-bold.bg-white.pt-5.pb-3.w-full";
        const button = await page.$(buttonSelector);
        if (button) {
            await button.click();
            // // Wait for 200ms after clicking the button
            await delay(200);
        }

        const htmlContent = await page.content();
        return htmlContent;
    } finally {
        await browser.close();
    }
}

// Sử dụng function crawlWebsite
const url = process.argv[2];
crawlWebsite(url)
    .then((html) => {
        console.log(html);
    })
    .catch((err) => {
        console.error(err);
    });
