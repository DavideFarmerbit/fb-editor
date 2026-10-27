import { containerEditor } from './Library/Container/edit';
import { iconEditor } from './Library/Icon/edit';
import { separatorEditor } from './Library/Separator/edit';
import { registerBlocks } from './register';

registerBlocks( [ containerEditor, iconEditor, separatorEditor ] );
