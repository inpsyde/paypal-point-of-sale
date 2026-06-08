/** Shared type definitions for test resources (fixtures, files, static data). */

export interface PluginZipEntry {
    /** Absolute path to the zip file inside resources/files/ */
    path: string;
    /** WordPress plugin slug (directory name inside the zip) */
    slug: string;
}
