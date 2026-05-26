const fs = require('fs');
const content = fs.readFileSync('html/watxio_mobile_glass_ui.html', 'utf8');
const bodyMatch = content.match(/<body[^>]*>([\s\S]*)<\/body>/i);
if (bodyMatch) {
  let html = bodyMatch[1].replace(/style="[^"]*"/g, '');
  console.log(html);
}
