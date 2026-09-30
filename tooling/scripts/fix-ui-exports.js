const fs = require('fs');
const path = require('path');

const uiDir = path.join(process.cwd(), 'apps/web/components/ui');

fs.writeFileSync(path.join(uiDir, 'accordion.tsx'), 'export { Accordion, AccordionItem, AccordionTrigger, AccordionContent } from "@navdhya/design-system";\n');
fs.writeFileSync(path.join(uiDir, 'button.tsx'), 'export { Button, buttonVariants } from "@navdhya/design-system";\nexport type { ButtonProps } from "@navdhya/design-system";\n');
fs.writeFileSync(path.join(uiDir, 'dropdown-menu.tsx'), 'export { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem, DropdownMenuCheckboxItem, DropdownMenuRadioItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuShortcut, DropdownMenuGroup, DropdownMenuPortal, DropdownMenuSub, DropdownMenuSubContent, DropdownMenuSubTrigger, DropdownMenuRadioGroup } from "@navdhya/design-system";\n');
fs.writeFileSync(path.join(uiDir, 'input.tsx'), 'export { Input } from "@navdhya/design-system";\nexport type { InputProps } from "@navdhya/design-system";\n');
fs.writeFileSync(path.join(uiDir, 'label.tsx'), 'export { Label } from "@navdhya/design-system";\n');
fs.writeFileSync(path.join(uiDir, 'select.tsx'), 'export { Select, SelectGroup, SelectValue, SelectTrigger, SelectContent, SelectLabel, SelectItem, SelectSeparator, SelectScrollUpButton, SelectScrollDownButton } from "@navdhya/design-system";\n');
fs.writeFileSync(path.join(uiDir, 'separator.tsx'), 'export { Separator } from "@navdhya/design-system";\n');
fs.writeFileSync(path.join(uiDir, 'sheet.tsx'), 'export { Sheet, SheetPortal, SheetOverlay, SheetTrigger, SheetClose, SheetContent, SheetHeader, SheetFooter, SheetTitle, SheetDescription } from "@navdhya/design-system";\n');
fs.writeFileSync(path.join(uiDir, 'textarea.tsx'), 'export { Textarea } from "@navdhya/design-system";\nexport type { TextareaProps } from "@navdhya/design-system";\n');
