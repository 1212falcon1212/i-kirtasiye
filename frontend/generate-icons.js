const sharp = require('sharp');
const fs = require('fs');
const path = require('path');

const sizes = [72, 96, 128, 144, 152, 192, 384, 512];
const pngPath = path.join(__dirname, 'public/favicon.png');
const outputDir = path.join(__dirname, 'public/icons');

async function generateIcons() {
    const pngBuffer = fs.readFileSync(pngPath);

    for (const size of sizes) {
        const outputPath = path.join(outputDir, `icon-${size}x${size}.png`);
        await sharp(pngBuffer)
            .resize(size, size)
            .png()
            .toFile(outputPath);
        console.log(`✅ Created: icon-${size}x${size}.png`);
    }

    // Create apple-touch-icon
    await sharp(pngBuffer)
        .resize(180, 180)
        .png()
        .toFile(path.join(outputDir, 'apple-touch-icon.png'));
    console.log('✅ Created: apple-touch-icon.png');

    // Create icon.svg (from png)
    await sharp(pngBuffer)
        .resize(512, 512)
        .png()
        .toFile(path.join(outputDir, 'icon.svg'));
    console.log('✅ Created: icon.svg');
}

generateIcons().then(() => {
    console.log('\n🎉 All icons generated successfully!');
}).catch(err => {
    console.error('Error:', err);
});
