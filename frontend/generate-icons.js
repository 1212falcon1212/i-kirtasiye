const sharp = require('sharp');
const fs = require('fs');
const path = require('path');

const sizes = [72, 96, 128, 144, 152, 192, 384, 512];
const svgPath = path.join(__dirname, 'public/icons/icon.svg');
const outputDir = path.join(__dirname, 'public/icons');

async function generateIcons() {
    const svgBuffer = fs.readFileSync(svgPath);

    for (const size of sizes) {
        const outputPath = path.join(outputDir, `icon-${size}x${size}.png`);
        await sharp(svgBuffer)
            .resize(size, size)
            .png()
            .toFile(outputPath);
        console.log(`✅ Created: icon-${size}x${size}.png`);
    }

    // Also create favicon
    await sharp(svgBuffer)
        .resize(32, 32)
        .png()
        .toFile(path.join(__dirname, 'public/favicon.png'));
    console.log('✅ Created: favicon.png');

    // Create apple-touch-icon
    await sharp(svgBuffer)
        .resize(180, 180)
        .png()
        .toFile(path.join(outputDir, 'apple-touch-icon.png'));
    console.log('✅ Created: apple-touch-icon.png');
}

generateIcons().then(() => {
    console.log('\n🎉 All icons generated successfully!');
}).catch(err => {
    console.error('Error:', err);
});
