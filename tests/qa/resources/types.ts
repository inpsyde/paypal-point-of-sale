/** Shared type definitions for test resources (fixtures, files, static data). */

export interface PluginZipEntry {
    /** Full visible name of the plugin as shown in the WordPress admin */
    name: string;
    /** WordPress plugin slug (directory name inside the zip) */
    slug: string;
    /** Absolute path to the zip file inside resources/files/ */
    zipFilePath: string;
}
