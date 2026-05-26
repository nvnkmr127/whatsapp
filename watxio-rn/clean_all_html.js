const fs = require('fs');
const path = require('path');

const mappings = {
  '02 _ Chat.html': 'clean_chat.html',
  '03 _ Contact.html': 'clean_contact.html',
  '04 _ Broadcast.html': 'clean_broadcast.html',
  '06 _ Analytics.html': 'clean_analytics.html',
  '07 _ Automations.html': 'clean_automations.html',
  '08 _ Settings.html': 'clean_settings.html',
  '09 _ Onboarding.html': 'clean_onboarding.html'
};

const importantProps = [
  'background', 'background-color', 'background-image',
  'backdrop-filter', '-webkit-backdrop-filter',
  'border', 'border-color', 'border-width', 'border-style', 'border-radius',
  'box-shadow', 'opacity', 'color',
  'font-size', 'font-weight', 'font-family', 'text-transform',
  'margin', 'margin-top', 'margin-bottom', 'margin-left', 'margin-right',
  'padding', 'padding-top', 'padding-bottom', 'padding-left', 'padding-right',
  'display', 'flex-direction', 'align-items', 'justify-content', 'gap', 'flex',
  'position', 'top', 'bottom', 'left', 'right', 'z-index',
  'width', 'height', 'max-width', 'max-height', 'overflow'
];

for (const [src, dest] of Object.entries(mappings)) {
  const filePath = path.join('html', src);
  if (!fs.existsSync(filePath)) {
    console.log(`File not found: ${filePath}`);
    continue;
  }
  
  const content = fs.readFileSync(filePath, 'utf8');
  const bodyMatch = content.match(/<body[^>]*>([\s\S]*)<\/body>/i);
  if (!bodyMatch) {
    console.log(`Body not found in: ${src}`);
    continue;
  }
  
  let bodyHtml = bodyMatch[1];
  
  // Clean style attributes
  bodyHtml = bodyHtml.replace(/style="([^"]*)"/g, (match, styleStr) => {
    const cleanStyles = styleStr.split(';')
      .map(s => s.trim())
      .filter(s => {
        if (!s) return false;
        const colonIndex = s.indexOf(':');
        if (colonIndex === -1) return false;
        const prop = s.substring(0, colonIndex).trim().toLowerCase();
        const val = s.substring(colonIndex + 1).trim().toLowerCase();
        
        if (val === 'auto' || val === 'normal' || val === 'none' || val === 'transparent' || val === '0px') {
          if (prop !== 'background' && prop !== 'background-color' && prop !== 'display' && prop !== 'overflow') {
            return false;
          }
        }
        return importantProps.includes(prop);
      })
      .join('; ');
    
    return cleanStyles ? `style="${cleanStyles}"` : '';
  });
  
  // Remove IDs
  bodyHtml = bodyHtml.replace(/\s*data-om-id="[^"]*"/g, '');
  bodyHtml = bodyHtml.replace(/\s*data-om-text="[^"]*"/g, '');
  
  fs.writeFileSync(dest, bodyHtml);
  console.log(`Successfully wrote ${dest}`);
}
