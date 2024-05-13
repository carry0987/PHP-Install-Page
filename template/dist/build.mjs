import { rollup, watch } from 'rollup';
import typescript from '@rollup/plugin-typescript';
import terser from '@rollup/plugin-terser';
import resolve from '@rollup/plugin-node-resolve';
import path from 'path';
import { deleteAsync } from 'del';

const isProduction = process.env.BUILD === 'production';
const isWatch = process.env.BUILD === 'watch';
const globals = {
    '@carry0987/utils-full': 'Utils',
    'sweetalert2': 'Swal',
    'select2': 'Select2'
};
let activeWatcher = null;

function getCurrentTimestamp() {
    const now = new Date();
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const seconds = now.getSeconds().toString().padStart(2, '0');

    return `${hours}:${minutes}:${seconds}`;
}

function determineExternal(id) {
    const externalLibs = ['@carry0987/', 'sweetalert', 'select2'];
    const internalLibs = ['@carry0987/utils'];

    return externalLibs.some(lib => id.startsWith(lib)) && !internalLibs.some(lib => id.endsWith(lib));
}

function getRollupOptions(file) {
    return {
        input: path.join('dist', 'ts', file),
        plugins: [
            typescript({ tsconfig: './tsconfig.json' }),
            resolve(),
            isProduction && terser()
        ],
        external: (id) => determineExternal(id)
    };
}

function getOutputOptions(file) {
    const outputPath = path.join('dist', 'js', file.replace(/\.ts$/, '.min.js'));
    return {
        file: outputPath,
        format: 'umd',
        name: 'InstallHelper',
        sourcemap: false,
        globals: globals
    };
}

async function buildFile(file, watchMode = false) {
    console.log(`[${getCurrentTimestamp()}] Building ${file}...`);
    const rollupOptions = getRollupOptions(file);
    const outputOptions = getOutputOptions(file);
    if (watchMode) {
        activeWatcher = watch({
            ...rollupOptions,
            output: [outputOptions],
        });
        activeWatcher.on('event', (event) => {
            if (event.code === 'END') {
                console.log(`[${getCurrentTimestamp()}] Rebuilt ${file}`);
            }
        });
    } else {
        const bundle = await rollup(rollupOptions);
        await bundle.write(outputOptions);
        await deleteAsync(['dist/js/interface', 'dist/js/type']);
    }
}

process.on('SIGTERM', () => {
    if (activeWatcher) {
        activeWatcher.close();
    }
});

(async () => {
    await buildFile('install.ts', isWatch);
})();
