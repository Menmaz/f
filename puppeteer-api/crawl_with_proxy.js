const puppeteer = require('puppeteer');

async function crawlWebsiteWithProxy(url, proxyList) {
    for (let proxy of proxyList) {
        let browser;

        try {
            browser = await puppeteer.launch({
                args: [`--proxy-server=${proxy}`],
            });
            const page = await browser.newPage();

            try {
                await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
                await page.goto(url, {
                    waitUntil: 'networkidle0',
                    referer: 'https://truyenvn.me'
                });

                const chapterReadmoreButton = await page.$('div.c-chapter-readmore');
                if (chapterReadmoreButton) {
                    await chapterReadmoreButton.click();
                    await delay(3000);
                }

                const htmlContent = await page.content();
                return htmlContent;
            } catch (err) {
                console.error(`Error processing page with proxy ${proxy}:`, err);
            } finally {
                await page.close();
            }
        } catch (err) {
            console.error(`Error launching browser with proxy ${proxy}:`, err);
        } finally {
            if (browser) {
                await browser.close();
            }
        }
    }

    throw new Error('All proxies failed');
}

// Function to introduce delay
function delay(time) {
    return new Promise(function(resolve) {
        setTimeout(resolve, time);
    });
}

// Example usage:
const proxyList = [
    '104.207.50.81:3128',
    '104.167.29.36:3128',
    '104.167.25.188:3128',
    '104.207.63.36:3128',
    '104.207.34.224:3128'
];

const url = process.argv[2];
crawlWebsiteWithProxy(url, proxyList).then((html) => {
    console.log(html);
}).catch((err) => {
    console.error(err);
});
